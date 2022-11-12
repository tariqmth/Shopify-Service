<?php

namespace App\Models\Syncer;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Client\Client;
use App\Models\Mapper\ShopifyVoucherAdjustmentMapper;
use App\Models\Mapper\ShopifyVoucherMapper;
use App\Models\Order\RexOrder;
use App\Models\Store\RexSalesChannel;
use App\Models\Voucher\RexVoucher;
use App\Models\Voucher\RexVoucherRepository;
use App\Models\Voucher\ShopifyVoucher;
use App\Models\Voucher\ShopifyVoucherRepository;
use App\Packages\SkylinkSdkFactory;
use App\Queues\Jobs\ProcessRexEDSNotification;
use App\Queues\Jobs\SyncAllRexVouchersOut;
use App\Queues\Jobs\SyncRexVoucherOut;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RetailExpress\SkyLink\Sdk\Apis\V2\Api as RexClient;
use RetailExpress\SkyLink\Sdk\Vouchers\V2VoucherRepository as RexVoucherClient;
use RetailExpress\SkyLink\Sdk\Vouchers\VoucherRedemption as RexVoucherRedemptionData;
use ValueObjects\Web\Url;
use ValueObjects\Identity\UUID as Uuid;
use ValueObjects\StringLiteral\StringLiteral;

class RexVoucherSyncer extends RexSyncer
{
    const MAX_JOBS = 1000;

    protected $shopifyVoucherRepository;
    protected $shopifyVoucherMapper;
    protected $shopifyVoucherSyncer;
    protected $rexVoucherRepository;
    protected $shopifyVoucherAdjustmentMapper;
    protected $skylinkSdkFactory;

    public function __construct(
        ShopifyVoucherRepository $shopifyVoucherRepository,
        ShopifyVoucherMapper $shopifyVoucherMapper,
        ShopifyVoucherSyncer $shopifyVoucherSyncer,
        RexVoucherRepository $rexVoucherRepository,
        ShopifyVoucherAdjustmentMapper $shopifyVoucherAdjustmentMapper,
        SkylinkSdkFactory $skylinkSdkFactory
    ) {
        $this->shopifyVoucherRepository = $shopifyVoucherRepository;
        $this->shopifyVoucherMapper = $shopifyVoucherMapper;
        $this->shopifyVoucherSyncer = $shopifyVoucherSyncer;
        $this->rexVoucherRepository = $rexVoucherRepository;
        $this->shopifyVoucherAdjustmentMapper = $shopifyVoucherAdjustmentMapper;
        $this->skylinkSdkFactory = $skylinkSdkFactory;
    }

    public function syncOut(RexVoucher $rexVoucher)
    {
        SyncRexVoucherOut::dispatch($rexVoucher)
            ->onConnection('database_sync')
            ->onQueue('voucher');
    }

    public function performSyncOut($rexVoucherId)
    {
        $rexVoucher = RexVoucher::findOrFail($rexVoucherId);
        $client = $rexVoucher->rexSalesChannel->client;
        $this->limitApiCalls($client);
        $rexVoucherData = $this->fetchRexVoucherData($rexVoucher);

        if (!isset($rexVoucherData)) {
            throw new ImpossibleTaskException('Rex voucher could not be found in Retail Express.');
        }

        $requiresSave = false;

        if ($rexVoucherData->getCode() !== null && $rexVoucherData->getCode()->toNative() !== $rexVoucher->code) {
            $rexVoucher->code = $rexVoucherData->getCode()->toNative();
            $requiresSave = true;
        }

        if ($rexVoucherData->getOrderId() !== null) {
            $rexOrder = RexOrder
                ::where('rex_sales_channel_id', $rexVoucher->rex_sales_channel_id)
                ->where('external_id', $rexVoucherData->getOrderId())
                ->first();
            if (isset($rexOrder)) {
                $rexVoucher->rex_order_id = $rexOrder->id;
                $requiresSave = true;
            }
        }

        if ($requiresSave) {
            $rexVoucher->save();
        }

        $shopifyVoucher = $rexVoucher->shopifyVoucher;
        if (!isset($shopifyVoucher)) {
            $shopifyVoucher = $this->shopifyVoucherRepository->createForRexVoucher($rexVoucher);
        }

        if (!$shopifyVoucher->hasBeenSynced()) {
            $mappedData = $this->shopifyVoucherMapper->getMappedData($rexVoucher, $rexVoucherData);
            $this->shopifyVoucherSyncer->syncIn($shopifyVoucher, $mappedData);
        }

        if ($rexVoucherData->getRedemptions() !== null) {
            foreach ($rexVoucherData->getRedemptions() as $voucherRedemptionData) {
                $this->processRedemption($rexVoucher, $shopifyVoucher, $voucherRedemptionData);
            }
        }
    }

    public function syncAllOut(RexSalesChannel $rexSalesChannel)
    {
        SyncAllRexVouchersOut::dispatch($rexSalesChannel)
            ->onConnection('database_sync')
            ->onQueue('all_vouchers');
    }

    public function performSyncAllOut($rexSalesChannelId)
    {
        $rexSalesChannel = RexSalesChannel::findOrFail($rexSalesChannelId);

        $voucherIds = $this->fetchRexVoucherIds($rexSalesChannel);

        $count = count($voucherIds);
        if ($count > self::MAX_JOBS) {
            for ($i = 0; $i < $count; $i += self::MAX_JOBS) {
                $notification = new \stdClass();
                $notification->Type = 'Voucher';
                $notification->List = [];
                for ($id = $i; $id < $count && $id < $i + self::MAX_JOBS; $id++) {
                    $notification->List[] = $voucherIds[$id]->toNative();
                }
                ProcessRexEDSNotification::dispatch(
                    $rexSalesChannel->client->external_id,
                    $notification,
                    $rexSalesChannel->external_id)
                    ->onConnection('database_sync')
                    ->onQueue('notification');
            }
        } else {
            foreach ($voucherIds as $voucherId) {
                $voucherId = $voucherId->toNative();
                $voucher = $this->rexVoucherRepository->get($rexSalesChannelId, $voucherId);
                if (!isset($voucher)) {
                    $voucher = $this->rexVoucherRepository->create($rexSalesChannelId, $voucherId);
                }
                $this->syncOut($voucher);
            }
        }
    }

    private function fetchRexVoucherIds(RexSalesChannel $rexSalesChannel)
    {
        $rexVoucherClient = $this->getRexVoucherClient($rexSalesChannel->client);
        return $rexVoucherClient->allIds($rexSalesChannel->external_id);
    }

    private function fetchRexVoucherData(RexVoucher $rexVoucher)
    {
        $rexSalesChannel = $rexVoucher->rexSalesChannel;
        $rexVoucherClient = $this->getRexVoucherClient($rexSalesChannel->client);
        return $rexVoucherClient->findById($rexSalesChannel->external_id, $rexVoucher->external_id);
    }

    private function getRexVoucherClient(Client $client)
    {
        $api = $this->skylinkSdkFactory->getApi($client);
        return new RexVoucherClient($api);
    }

    private function processRedemption(
        RexVoucher $rexVoucher,
        ShopifyVoucher $shopifyVoucher,
        RexVoucherRedemptionData $voucherRedemptionData
    ) {
        $rexVoucherRedemption = $this->rexVoucherRepository->getRedemption(
            $rexVoucher->id,
            $voucherRedemptionData->getOrderPaymentId()
        );
        if (!isset($rexVoucherRedemption)) {
            $rexVoucherRedemption = $this->rexVoucherRepository->createRedemption(
                $rexVoucher->id,
                $voucherRedemptionData->getOrderPaymentId(),
                $voucherRedemptionData->getPayment()
            );
        }

        $existingPaymentCount = DB::table('rex_payments')
            ->join('rex_orders', 'rex_orders.id', '=', 'rex_payments.rex_order_id')
            ->where('rex_payments.external_id', $rexVoucherRedemption->rex_payment_external_id)
            ->where('rex_orders.rex_sales_channel_id', $rexVoucherRedemption->rexVoucher->rex_sales_channel_id)
            ->count();
        if ($existingPaymentCount) {
            Log::debug('Skipping Rex voucher redemption ' . $rexVoucherRedemption->id
                . ' as it corresponds to a payment from the same sales channel.');
            return;
        }

        $shopifyVoucherAdjustment = $rexVoucherRedemption->shopifyVoucherAdjustment;
        if (!isset($shopifyVoucherAdjustment)) {
            $shopifyVoucherAdjustment = $this->shopifyVoucherRepository->createAdjustment(
                $shopifyVoucher->id,
                $rexVoucherRedemption->id
            );
        }
        if (!$shopifyVoucherAdjustment->hasBeenSynced()) {
            $mappedAdjustmentData = $this->shopifyVoucherAdjustmentMapper->getMappedData($rexVoucherRedemption);
            $this->shopifyVoucherSyncer->syncInAdjustment($shopifyVoucherAdjustment, $mappedAdjustmentData);
        }
    }
}