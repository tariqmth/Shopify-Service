<?php

namespace App\Models\Syncer;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Client\Client;
use App\Models\Mapper\RexOrderMapperFromShopify;
use App\Models\Mapper\RexPaymentMapperFromShopify;
use App\Models\Order\RexOrder;
use App\Models\Payment\RexPayment;
use App\Models\Voucher\ShopifyVoucherRepository;
use App\Packages\SkylinkSdkFactory;
use App\Queues\Jobs\SyncRexOrderIn;
use App\Queues\Jobs\SyncRexPaymentIn;
use RetailExpress\SkyLink\Sdk\Apis\V2\Api as RexClient;
use RetailExpress\SkyLink\Sdk\Sales\Orders\V2OrderRepository as RexOrderClient;
use RetailExpress\SkyLink\Sdk\Sales\Payments\V2PaymentRepository as RexPaymentClient;
use ValueObjects\Web\Url;
use ValueObjects\Identity\UUID as Uuid;
use ValueObjects\StringLiteral\StringLiteral;
use RetailExpress\SkyLink\Sdk\ValueObjects\SalesChannelId as RexSalesChannelIdData;

class RexPaymentSyncer extends RexSyncer
{
    protected $rexPaymentMapperFromShopify;
    protected $shopifyVoucherRepository;
    protected $skylinkSdkFactory;

    public function __construct(
        RexPaymentMapperFromShopify $rexPaymentMapperFromShopify,
        ShopifyVoucherRepository $shopifyVoucherRepository,
        SkylinkSdkFactory $skylinkSdkFactory
    ) {
        $this->rexPaymentMapperFromShopify = $rexPaymentMapperFromShopify;
        $this->shopifyVoucherRepository = $shopifyVoucherRepository;
        $this->skylinkSdkFactory = $skylinkSdkFactory;
    }

    public function syncOut(RexPayment $rexPayment)
    {
        // todo
    }

    public function syncInFromShopify(RexPayment $rexPayment, $shopifyTransactionData)
    {
        SyncRexPaymentIn::dispatch($rexPayment, $shopifyTransactionData)
            ->onConnection('database_sync')
            ->onQueue('payment')
            ->delay(now()->addMinutes(1));
    }

    public function performSyncInFromShopify($rexPaymentId, $shopifyTransactionData)
    {
        $rexPayment = RexPayment::findOrFail($rexPaymentId);
        if ($rexPayment->hasBeenSynced()) {
            throw new ImpossibleTaskException('Rex payments can only be created once and cannot be updated.');
        }

        if ($shopifyTransactionData->gateway === 'gift_card') {
            try {
                $shopifyStoreId = $rexPayment->rexOrder->rexSalesChannel->shopifyStore->id;
                $shopifyGiftCardExternalId = $shopifyTransactionData->receipt->gift_card_id;
                $shopifyVoucher = $this->shopifyVoucherRepository->get($shopifyStoreId, $shopifyGiftCardExternalId);
                $rexVoucherCode = $shopifyVoucher->rexVoucher->code;
                if (!isset($rexVoucherCode)) {
                    throw new \Exception('Code for Retail Express voucher is not set.');
                }
            } catch (\Exception $e) {
                throw new ImpossibleTaskException('Error trying to get gift card used in Shopify transaction.' , 0, $e);
            }
        } else {
            $rexVoucherCode = null;
        }

        $mappedData = $this->rexPaymentMapperFromShopify->getMappedData(
            $rexPayment,
            $shopifyTransactionData,
            $rexVoucherCode
        );

        $client = $rexPayment->rexOrder->rexSalesChannel->client;
        $this->limitApiCalls($client);
        $rexPaymentClient = $this->getRexPaymentClient($client);
        $rexPaymentClient->add($mappedData);
        $rexPayment->fresh();

        if ($rexPayment->hasBeenSynced()) {
            throw new ImpossibleTaskException('Race condition! Payment has been duplicated in Rex.');
        }

        if ($mappedData->getId() !== null) {
            $rexPayment->external_id = $mappedData->getId()->toNative();
            $rexPayment->save();
        } else {
            throw new \Exception('Payment not synced to Rex correctly.');
        }
    }

    private function getRexPaymentClient(Client $client)
    {
        $api = $this->skylinkSdkFactory->getApi($client);
        $orderClient = new RexOrderClient($api);
        return new RexPaymentClient($api, $orderClient);
    }
}
