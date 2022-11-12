<?php

namespace App\Models\Notification;

use App\Models\Customer\ShopifyCustomerRepository;
use App\Models\Fulfillment\ShopifyFulfillmentRepository;
use App\Models\Order\ShopifyOrderRepository;
use App\Models\Payment\ShopifyPaymentGatewayRepository;
use App\Models\Payment\ShopifyTransactionRepository;
use App\Models\Product\ShopifyProductRepository;
use App\Models\Syncer\ShopifyCustomerSyncer;
use App\Models\Syncer\ShopifyOrderSyncer;
use App\Models\Syncer\ShopifyProductSyncer;
use App\Models\Syncer\ShopifyStoreSyncer;
use App\Models\Syncer\ShopifyTransactionSyncer;
use App\Models\Store\ShopifyStore;
use Illuminate\Support\Facades\Log;
use Validator;

class ShopifyWebhookNotificationHandler
{
    protected $shopifyOrderRepository;
    protected $shopifyOrderSyncer;
    protected $shopifyCustomerRepository;
    protected $shopifyCustomerSyncer;
    protected $shopifyTransactionRepository;
    protected $shopifyPaymentGatewayRepository;
    protected $shopifyTransactionSyncer;
    protected $shopifyProductRepository;
    protected $shopifyProductSyncer;
    protected $shopifyStoreSyncer;
    protected $shopifyFulfillmentRepository;

    public function __construct(
        ShopifyOrderRepository $shopifyOrderRepository,
        ShopifyOrderSyncer $shopifyOrderSyncer,
        ShopifyCustomerRepository $shopifyCustomerRepository,
        ShopifyCustomerSyncer $shopifyCustomerSyncer,
        ShopifyTransactionRepository $shopifyTransactionRepository,
        ShopifyPaymentGatewayRepository $shopifyPaymentGatewayRepository,
        ShopifyTransactionSyncer $shopifyTransactionSyncer,
        ShopifyProductRepository $shopifyProductRepository,
        ShopifyProductSyncer $shopifyProductSyncer,
        ShopifyStoreSyncer $shopifyStoreSyncer,
        ShopifyFulfillmentRepository $shopifyFulfillmentRepository
    ) {
        $this->shopifyOrderRepository = $shopifyOrderRepository;
        $this->shopifyOrderSyncer = $shopifyOrderSyncer;
        $this->shopifyCustomerRepository = $shopifyCustomerRepository;
        $this->shopifyCustomerSyncer = $shopifyCustomerSyncer;
        $this->shopifyTransactionRepository = $shopifyTransactionRepository;
        $this->shopifyPaymentGatewayRepository = $shopifyPaymentGatewayRepository;
        $this->shopifyTransactionSyncer = $shopifyTransactionSyncer;
        $this->shopifyProductRepository = $shopifyProductRepository;
        $this->shopifyProductSyncer = $shopifyProductSyncer;
        $this->shopifyStoreSyncer = $shopifyStoreSyncer;
        $this->shopifyFulfillmentRepository = $shopifyFulfillmentRepository;
    }

    public function process($domainName, $topic, $notificationBody)
    {
        Log::debug('Processing Shopify webhook notification', [
            'subdomain' => $domainName,
            'topic' => $topic,
            'notification_body' => $notificationBody
        ]);

        $subdomain = strtok($domainName, '.');
        $shopifyStore = ShopifyStore::where('subdomain', $subdomain)->firstOrFail();
        if (!$shopifyStore->enabled) {
            throw new \Exception('Shopify store ' . $subdomain . ' is not enabled for syncing.');
        }

        $entityData = json_decode($notificationBody);

        if ($topic === 'orders/create') {
            $this->syncOrder($shopifyStore, $entityData);
        } elseif ($topic === 'customers/create') {
            $this->syncCustomer($shopifyStore, $entityData);
        } elseif ($topic === 'order_transactions/create') {
            $this->syncTransaction($shopifyStore, $entityData);
        } elseif ($topic === 'app/uninstalled') {
            $this->disconnectStore($shopifyStore);
        } elseif ($topic === 'shop/update') {
            $this->syncStore($shopifyStore, $entityData);
        } elseif ($topic === 'fulfillments/create') {
            $this->saveFulfillment($shopifyStore, $entityData);
        } else {
            throw new \Exception('Shopify topic ' . $topic . ' is not supported.');
        }
    }

    protected function syncOrder(ShopifyStore $shopifyStore, $shopifyOrderData)
    {
        $shopifyOrder = $this->shopifyOrderRepository->getOrCreate($shopifyStore->id, $shopifyOrderData->id);
        $this->shopifyOrderSyncer->syncOut($shopifyOrder, $shopifyOrderData);
    }

    protected function syncCustomer(ShopifyStore $shopifyStore, $shopifyCustomerData)
    {
        $shopifyCustomer = $this->shopifyCustomerRepository->getOrCreate($shopifyStore->id, $shopifyCustomerData->id);
        if ($shopifyCustomer->email !== $shopifyCustomerData->email) {
            $shopifyCustomer->email = $shopifyCustomerData->email;
            $shopifyCustomer->save();
        }
        if (!isset($shopifyCustomer->rexCustomer)) {
            $this->shopifyCustomerSyncer->syncOut($shopifyCustomer, $shopifyCustomerData);
        }
    }

    protected function syncTransaction(ShopifyStore $shopifyStore, $shopifyTransactionData)
    {
        $shopifyOrder = $this->shopifyOrderRepository->get($shopifyStore->id, $shopifyTransactionData->order_id);
        if (!isset($shopifyOrder)) {
            throw new \Exception('Shopify order for transaction does not exist or has not been synced yet.');
        }

        if ($shopifyTransactionData->kind !== 'capture' && $shopifyTransactionData->kind !== 'sale') {
            throw new \Exception('Shopify transaction must be a capture or sale.');
        }

        if ($shopifyTransactionData->status !== 'success') {
            throw new \Exception('Shopify transaction must be successful to be synced to Rex.');
        }

        $shopifyPaymentGateway = $this->shopifyPaymentGatewayRepository->get($shopifyTransactionData->gateway);
        // Zip pay sometimes returns zip or zip_own_it_now_pay_later
        if (!isset($shopifyPaymentGateway) && $shopifyTransactionData->gateway === 'zip') {
            $shopifyPaymentGateway = $this->shopifyPaymentGatewayRepository->get('zip_own_it_now_pay_later');
        }
        if (!isset($shopifyPaymentGateway)) {
            $shopifyPaymentGateway = $this->shopifyPaymentGatewayRepository->get('default');
        }

        $shopifyTransaction = $this->shopifyTransactionRepository->getOrCreate(
            $shopifyOrder->id,
            $shopifyPaymentGateway->id,
            $shopifyTransactionData->id
        );

        $this->shopifyTransactionSyncer->syncOut($shopifyTransaction, $shopifyTransactionData);
    }

    /*
     * @deprecated
     */
    protected function syncProduct(ShopifyStore $shopifyStore, $shopifyProductData)
    {
        $this->shopifyProductSyncer->saveProductData($shopifyStore, $shopifyProductData);
    }

    protected function disconnectStore(ShopifyStore $shopifyStore)
    {
        $shopifyStore->enabled = false;
        $shopifyStore->setup_status = ShopifyStore::SETUP_STATUS_RECONNECT;
        $shopifyStore->clearCredentials();
        $shopifyStore->save();
        $rexSalesChannel = $shopifyStore->rexSalesChannel;
        $shopifyStore->deleteAllChildren();
        $rexSalesChannel->deleteAllChildren();
    }

    protected function syncStore(ShopifyStore $shopifyStore, $shopifyStoreData)
    {
        $this->shopifyStoreSyncer->syncOut($shopifyStore, $shopifyStoreData);
    }

    protected function saveFulfillment(ShopifyStore $shopifyStore, $shopifyFulfillmentData)
    {
        $shopifyOrder = $this
            ->shopifyOrderRepository
            ->getOrCreate($shopifyStore->id, $shopifyFulfillmentData->order_id);
        $shopifyFulfillment = $this
            ->shopifyFulfillmentRepository
            ->getOrCreateByExternalId($shopifyOrder->id, $shopifyFulfillmentData->id);
        if ($shopifyFulfillmentData->status === 'success') {
            $shopifyFulfillment->complete = true;
            $shopifyFulfillment->save();
        }
        foreach ($shopifyFulfillmentData->line_items as $fulfillmentItemData) {
            $orderItem = $this->shopifyOrderRepository->getItem($shopifyOrder, $fulfillmentItemData->id);
            if (!isset($orderItem)) {
                throw new \Exception('Could not find Shopify order item to match fulfillment.');
            }
            $this->shopifyFulfillmentRepository
                ->getOrCreateItem($shopifyFulfillment->id, $orderItem->id, $fulfillmentItemData->quantity);
        }
    }
}
