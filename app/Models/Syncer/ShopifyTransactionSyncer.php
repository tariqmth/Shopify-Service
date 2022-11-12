<?php

namespace App\Models\Syncer;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ExternalEntityNotFoundException;
use App\Exceptions\ImpossibleTaskException;
use App\Exceptions\InvalidDataException;
use App\Models\Customer\RexCustomerRepository;
use App\Models\Customer\ShopifyCustomerRepository;
use App\Models\Mapper\RexOrderMapperFromShopify;
use App\Models\Notification\ShopifyWebhook;
use App\Models\Order\RexOrderRepository;
use App\Models\Payment\RexPaymentRepository;
use App\Models\Payment\ShopifyTransaction;
use App\Models\Voucher\ShopifyVoucherRepository;
use App\Packages\ShopifySdkFactory;
use App\Queues\Jobs\SyncShopifyTransactionIn;
use App\Queues\Jobs\SyncShopifyTransactionOut;
use App\Queues\Jobs\SyncShopifyWebhookIn;
use Illuminate\Support\Facades\Log;
use App\Models\Order\ShopifyOrder;

class ShopifyTransactionSyncer extends ShopifySyncer
{
    protected $shopifySdkFactory;
    protected $rexPaymentRepository;
    protected $rexPaymentSyncer;
    protected $shopifyVoucherRepository;

    public function __construct(
        ShopifySdkFactory $shopifySdkFactory,
        RexPaymentRepository $rexPaymentRepository,
        RexPaymentSyncer $rexPaymentSyncer,
        ShopifyVoucherRepository $shopifyVoucherRepository
    ) {
        $this->shopifySdkFactory = $shopifySdkFactory;
        $this->rexPaymentRepository = $rexPaymentRepository;
        $this->rexPaymentSyncer = $rexPaymentSyncer;
        $this->shopifyVoucherRepository = $shopifyVoucherRepository;
    }

    public function syncOut(ShopifyTransaction $shopifyTransaction, $shopifyTransactionData = null)
    {
        SyncShopifyTransactionOut::dispatch($shopifyTransaction, $shopifyTransactionData)
            ->onConnection('database_sync')
            ->onQueue('payment');
    }

    public function syncIn(ShopifyTransaction $shopifyTransaction, $shopifyTransactionData)
    {
        SyncShopifyTransactionIn::dispatch($shopifyTransaction, $shopifyTransactionData)
            ->onConnection('database_sync')
            ->onQueue('payment');
    }

    public function performSyncOut($shopifyTransactionId, $shopifyTransactionData = null)
    {
        $shopifyTransaction = ShopifyTransaction::findOrFail($shopifyTransactionId);
        $shopifyStore = $shopifyTransaction->shopifyOrder->shopifyStore;

        if (!isset($shopifyTransactionData) || $shopifyTransactionData->currency !== $shopifyStore->currency) {
            $shopifyTransactionData = $this->fetchShopifyTransactionData($shopifyTransaction);
        }

        if ($shopifyTransactionData->kind !== 'capture' && $shopifyTransactionData->kind !== 'sale') {
            throw new ImpossibleTaskException('Shopify transaction must be a capture or sale.');
        }

        if ($shopifyTransactionData->status !== 'success') {
            throw new ImpossibleTaskException('Shopify transaction must be successful to be synced to Rex.');
        }

        $rexPayment = $this->rexPaymentRepository->getOrCreate($shopifyTransaction);
        $this->rexPaymentSyncer->syncInFromShopify($rexPayment, $shopifyTransactionData);
    }

    public function performSyncIn($shopifyTransactionId, $shopifyTransactionData)
    {
        $shopifyTransaction = ShopifyTransaction::find($shopifyTransactionId);
        $shopifyStore = $shopifyTransaction->shopifyOrder->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);

        if ($shopifyTransaction->hasBeenSynced()) {
            throw new \Exception('Cannot sync a transaction into Shopify twice.');
        }

        $response = $shopifySdk->transactions->create(
            $shopifyTransaction->shopifyOrder->external_id,
            $shopifyTransactionData
        );

        $this->limitApiCalls($shopifyStore, $response->creditLeft());
        $body = $response->parsedResponse();

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }

        $shopifyTransaction->fresh();
        if ($shopifyTransaction->hasBeenSynced()) {
            throw new \Exception('Race condition! Duplicate payment created in Shopify.');
        }

        $shopifyTransaction->external_id = $body->id;
        $shopifyTransaction->save();
    }

    private function fetchShopifyTransactionData(ShopifyTransaction $shopifyTransaction)
    {
        $shopifyStore = $shopifyTransaction->shopifyOrder->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);
        $orderExternalId = $shopifyTransaction->shopifyOrder->external_id;
        $response = $shopifySdk->transactions->read(
            $shopifyTransaction->external_id,
            $orderExternalId,
            ['in_shop_currency' => true]
        );
        $this->limitApiCalls($shopifyStore, $response->creditLeft());

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            $this->handleEntityNotfound($shopifyTransaction);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }

        return $response->parsedResponse();
    }

    private function handleEntityNotfound(ShopifyTransaction $shopifyTransaction)
    {
        $shopifyStore = $shopifyTransaction->shopifyOrder->shopifyStore;
        Log::error('Shopify transaction ' . $shopifyTransaction->external_id
            . ' not found in Shopify store '
            . $shopifyStore->subdomain
            . '. Deleting.');
        $shopifyTransaction->delete();
    }
}
