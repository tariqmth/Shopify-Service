<?php

namespace App\Models\Syncer;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Client\Client;
use App\Models\Fulfillment\RexFulfillmentRepository;
use App\Models\Fulfillment\ShopifyFulfillmentRepository;
use App\Models\Mapper\RexOrderItemMapperFromShopify;
use App\Models\Mapper\RexOrderMapperFromShopify;
use App\Models\Mapper\ShopifyFulfillmentMapper;
use App\Models\Mapper\ShopifyTransactionMapper;
use App\Models\Order\RexOrder;
use App\Models\Order\RexOrderProduct;
use App\Models\Order\RexOrderRepository;
use App\Models\Order\ShopifyOrderPriceCalculator;
use App\Models\Order\ShopifyOrderRepository;
use App\Models\Payment\RexPaymentRepository;
use App\Models\Payment\ShopifyTransactionRepository;
use App\Models\Product\RexProduct;
use App\Models\Product\ShopifyProduct;
use App\Models\Product\ShopifyProductVariant;
use App\Packages\SkylinkSdkFactory;
use App\Queues\Jobs\SyncRexOrderIn;
use App\Queues\Jobs\SyncRexOrderOut;
use RetailExpress\SkyLink\Sdk\Apis\V2\Api as RexClient;
use RetailExpress\SkyLink\Sdk\Sales\Orders\OrderId as RexOrderIdData;
use RetailExpress\SkyLink\Sdk\Sales\Orders\V2OrderRepository as RexOrderClient;
use ValueObjects\Web\Url;
use ValueObjects\Identity\UUID as Uuid;
use ValueObjects\StringLiteral\StringLiteral;
use RetailExpress\SkyLink\Sdk\ValueObjects\SalesChannelId as RexSalesChannelIdData;

class RexOrderSyncer extends RexSyncer
{
    protected $rexOrderMapperFromShopify;
    protected $rexFulfillmentRepository;
    protected $shopifyFulfillmentRepository;
    protected $rexOrderRepository;
    protected $shopifyOrderRepository;
    protected $rexOrderItemMapperFromShopify;
    protected $shopifyFulfillmentMapper;
    protected $shopifyFulfillmentSyncer;
    protected $rexPaymentRepository;
    protected $shopifyTransactionSyncer;
    protected $shopifyTransactionRepository;
    protected $shopifyTransactionMapper;
    protected $shopifyOrderPriceCalculator;
    protected $skylinkSdkFactory;

    public function __construct(
        RexOrderMapperFromShopify $rexOrderMapperFromShopify,
        RexFulfillmentRepository $rexFulfillmentRepository,
        ShopifyFulfillmentRepository $shopifyFulfillmentRepository,
        RexOrderRepository $rexOrderRepository,
        ShopifyOrderRepository $shopifyOrderRepository,
        RexOrderItemMapperFromShopify $rexOrderItemMapperFromShopify,
        ShopifyFulfillmentMapper $shopifyFulfillmentMapper,
        ShopifyFulfillmentSyncer $shopifyFulfillmentSyncer,
        RexPaymentRepository $rexPaymentRepository,
        ShopifyTransactionSyncer $shopifyTransactionSyncer,
        ShopifyTransactionRepository $shopifyTransactionRepository,
        ShopifyTransactionMapper $shopifyTransactionMapper,
        ShopifyOrderPriceCalculator $shopifyOrderPriceCalculator,
        SkylinkSdkFactory $skylinkSdkFactory
    ) {
        $this->rexOrderMapperFromShopify = $rexOrderMapperFromShopify;
        $this->rexFulfillmentRepository = $rexFulfillmentRepository;
        $this->shopifyFulfillmentRepository = $shopifyFulfillmentRepository;
        $this->rexOrderRepository = $rexOrderRepository;
        $this->shopifyOrderRepository = $shopifyOrderRepository;
        $this->rexOrderItemMapperFromShopify = $rexOrderItemMapperFromShopify;
        $this->shopifyFulfillmentMapper = $shopifyFulfillmentMapper;
        $this->shopifyFulfillmentSyncer = $shopifyFulfillmentSyncer;
        $this->rexPaymentRepository = $rexPaymentRepository;
        $this->shopifyTransactionSyncer = $shopifyTransactionSyncer;
        $this->shopifyTransactionRepository = $shopifyTransactionRepository;
        $this->shopifyTransactionMapper = $shopifyTransactionMapper;
        $this->shopifyOrderPriceCalculator = $shopifyOrderPriceCalculator;
        $this->skylinkSdkFactory = $skylinkSdkFactory;
    }

    public function syncOut(RexOrder $rexOrder)
    {
        SyncRexOrderOut::dispatch($rexOrder)
            ->onConnection('database_sync')
            ->onQueue('order');
    }

    public function performSyncOut($rexOrderId)
    {
        $rexOrder = RexOrder::findOrFail($rexOrderId);

        if (!isset($rexOrder->shopifyOrder)) {
            throw new \Exception('Cannot sync out Rex order that has no associated Shopify order.');
        }

        $client = $rexOrder->rexSalesChannel->client;
        $this->limitApiCalls($client);

        $rexOrderData = $this->fetchRexOrderData($rexOrder);

        foreach ($rexOrderData->getItems() as $rexOrderItemData) {
            $rexOrderItem = $this->rexOrderRepository->getItemByOrder(
                $rexOrder->id,
                $rexOrderItemData->getId()->toNative()
            );
            if (!isset($rexOrderItem)) {
                if ($rexOrderItemData->getExternalItemId() !== null) {
                    $rexOrderProduct = $this->rexOrderRepository->getOrderProductById(
                        $rexOrder->id,
                        $rexOrderItemData->getExternalItemId()
                    );
                }
                if (!isset($rexOrderProduct)) {
                    $rexProduct = RexProduct
                        ::where('rex_sales_channel_id', $rexOrder->rex_sales_channel_id)
                        ->where('external_id', $rexOrderItemData->getProductId()->toNative())
                        ->first();
                    $rexOrderProduct = $this->rexOrderRepository->getOrderProduct(
                        $rexOrder->id,
                        $rexProduct->id,
                        $rexOrderItemData->getPrice()->toNative()
                    );
                }
                if (!isset($rexOrderProduct)) {
                    $rexOrderProduct = $this->rexOrderRepository->createOrderProduct(
                        $rexOrder,
                        $rexProduct,
                        $rexOrderItemData->getPrice()->toNative()
                    );
                }
                $this->rexOrderRepository->createItem(
                    $rexOrderProduct,
                    $rexOrderItemData->getId()->toNative()
                );
            }
        }

        foreach ($rexOrderData->getFulfillmentBatches() as $rexFulfillmentBatchData) {
            $rexFulfillmentBatch = $this->rexFulfillmentRepository->getOrCreateBatch(
                $rexOrder,
                $rexFulfillmentBatchData->getId()->toNative()
            );
            foreach ($rexFulfillmentBatchData->getFulfillments() as $rexFulfillmentData) {
                $this->rexFulfillmentRepository->getOrCreate(
                    $rexOrder,
                    $rexFulfillmentBatch,
                    $rexFulfillmentData->getId()->toNative()
                );
            }
            if ($rexFulfillmentBatch->rexFulfillments()->count()) {
                $shopifyFulfillment = $this->shopifyFulfillmentRepository->getOrCreate(
                    $rexFulfillmentBatch,
                    $rexFulfillmentBatchData
                );
                if (!$shopifyFulfillment->hasBeenSynced()) {
                    $mappedFulfillmentData = $this->shopifyFulfillmentMapper->getMappedData(
                        $shopifyFulfillment,
                        $rexFulfillmentBatchData
                    );
                    $this->shopifyFulfillmentSyncer->syncIn($shopifyFulfillment, $mappedFulfillmentData);
                } elseif (!$shopifyFulfillment->complete) {
                    $this->shopifyFulfillmentSyncer->syncInCompletion($shopifyFulfillment);
                }
            }
        }

        foreach ($rexOrderData->getPayments() as $rexPaymentData) {
            $rexPayment = $this->rexPaymentRepository->getOrCreateByExternalId(
                $rexOrder,
                $rexPaymentData->getId()->toNative(),
                $rexPaymentData->getMethodId()->toNative()
            );
            $shopifyTransaction = $this->shopifyTransactionRepository->getOrCreateForRexPayment($rexPayment);
            if (!$shopifyTransaction->hasBeenSynced()) {
                $shopifyStore = $shopifyTransaction->shopifyOrder->shopifyStore;
                $shopifyTransactionData = $this->shopifyTransactionMapper->getMappedData($rexPaymentData, $shopifyStore);
                $this->shopifyTransactionSyncer->syncIn($shopifyTransaction, $shopifyTransactionData);
            }
        }
    }

    public function syncInFromShopify(RexOrder $rexOrder, $shopifyOrderData)
    {
        SyncRexOrderIn::dispatch($rexOrder, $shopifyOrderData)
            ->onConnection('database_sync')
            ->onQueue('order');
    }

    public function performSyncInFromShopify($rexOrderId, $shopifyOrderData)
    {
        $rexOrder = RexOrder::findOrFail($rexOrderId);
        if ($rexOrder->hasBeenSynced()) {
            throw new ImpossibleTaskException('Rex orders (ID:'.$rexOrderId.') can only be created once and cannot be updated.');
        }

        $rexSalesChannel = $rexOrder->rexSalesChannel;

        $mappedData = $this->rexOrderMapperFromShopify->getMappedData($rexOrder, $shopifyOrderData);

        foreach ($shopifyOrderData->line_items as $shopifyLineItemData) {
            $priceAfterDiscount = $this->shopifyOrderPriceCalculator->getDiscountedItemPrice($shopifyLineItemData);

            $shopifyOrderItem = $this->shopifyOrderRepository->getItem(
                $rexOrder->shopifyOrder,
                $shopifyLineItemData->id
            );

            $rexOrderProduct = $shopifyOrderItem->rexOrderProduct;

            if (!isset($rexOrderProduct)) {
                if ($shopifyLineItemData->variant_id !== null) {
                    $shopifyVariant = ShopifyProductVariant
                        ::where('external_id', $shopifyLineItemData->variant_id)
                        ->firstOrFail();
                    $rexProduct = $shopifyVariant->rexProduct;
                } else {
                    $shopifyProduct = ShopifyProduct
                        ::where('external_id', $shopifyLineItemData->product_id)
                        ->firstOrFail();
                    $rexProduct = $shopifyProduct->rexProduct;
                }

                if (!isset($rexProduct)) {
                    throw new \Exception('Cannot find matching Rex product for Shopify order '
                        . $shopifyOrderData->id . ' line item ' . $shopifyLineItemData->id);
                }

                $rexOrderProduct = $this->rexOrderRepository->createOrderProduct(
                    $rexOrder,
                    $rexProduct,
                    $priceAfterDiscount
                );

                $shopifyOrderItem->rexOrderProduct()->associate($rexOrderProduct);
                $shopifyOrderItem->save();
            }

            $rexOrderItemData = $this->rexOrderItemMapperFromShopify->getMappedData(
                $rexOrderProduct,
                $shopifyLineItemData
            );

            $mappedData = $mappedData->withItem($rexOrderItemData);
        }

        $this->limitApiCalls($rexSalesChannel->client);
        $rexOrderClient = $this->getRexOrderClient($rexSalesChannel->client);
        $rexSalesChannelIdData = RexSalesChannelIdData::fromNative($rexSalesChannel->external_id);
        $result = $rexOrderClient->add($rexSalesChannelIdData, $mappedData);
        $rexOrder->fresh();

        if ($rexOrder->hasBeenSynced()) {
            throw new ImpossibleTaskException('Race condition! Order has been duplicated in Rex.');
        }

        if ($mappedData->getId() !== null) {
            $rexOrder->external_id = $mappedData->getId()->toNative();
            $rexOrder->save();
        } else {
            throw new \Exception('Order not synced to Rex correctly.');
        }

        $rexCustomer = $rexOrder->rexCustomer;

        if (isset($rexCustomer) && !$rexCustomer->hasBeenSynced() && $mappedData->getCustomerId() !== null) {
            $rexCustomer->external_id = $mappedData->getCustomerId()->toNative();
            $rexCustomer->save();
        }

        $this->syncOut($rexOrder);
    }

    private function getRexOrderClient(Client $client)
    {
        $api = $this->skylinkSdkFactory->getApi($client);
        return new RexOrderClient($api);
    }

    private function fetchRexOrderData(RexOrder $rexOrder)
    {
        $salesChannel = $rexOrder->rexSalesChannel;
        $client = $salesChannel->client;
        $orderClient = $this->getRexOrderClient($client);
        $orderIdData = new RexOrderIdData($rexOrder->external_id);
        $rexOrderData = $orderClient->get($orderIdData);

        if (!isset($rexOrderData)) {
            throw new \Exception('Could not retrieve order ' . $rexOrder->id . ' from REX.');
        }

        return $rexOrderData;
    }
}
