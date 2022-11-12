<?php

namespace App\Models\Syncer;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ExternalEntityNotFoundException;
use App\Exceptions\ImpossibleTaskException;
use App\Exceptions\InvalidDataException;
use App\Models\Customer\RexCustomerRepository;
use App\Models\Customer\ShopifyCustomerRepository;
use App\Models\Fulfillment\ShopifyFulfillment;
use App\Models\Fulfillment\ShopifyFulfillmentRepository;
use App\Models\Mapper\RexOrderMapperFromShopify;
use App\Models\Mapper\ShopifyVoucherFulfillmentMapper;
use App\Models\Order\RexOrderRepository;
use App\Models\Order\ShopifyOrderRepository;
use App\Models\Product\ShopifyProductRepository;
use App\Models\Product\ShopifyProductVariant;
use App\Packages\ShopifySdkFactory;
use App\Queues\Jobs\SyncShopifyOrderOut;
use Illuminate\Support\Facades\Log;
use App\Models\Order\ShopifyOrder;
use Illuminate\Support\Facades\DB;
class ShopifyOrderSyncer extends ShopifySyncer
{
    protected $shopifySdkFactory;
    protected $rexOrderMapperFromShopify;
    protected $rexOrderRepository;
    protected $rexOrderSyncer;
    protected $shopifyCustomerRepository;
    protected $rexCustomerRepository;
    protected $shopifyOrderRepository;
    protected $shopifyProductRepository;
    protected $shopifyVoucherFulfillmentMapper;
    protected $shopifyFulfillmentSyncer;
    protected $shopifyFulfillmentRepository;

    public function __construct(
        ShopifySdkFactory $shopifySdkFactory,
        RexOrderMapperFromShopify $rexOrderMapperFromShopify,
        RexOrderRepository $rexOrderRepository,
        RexOrderSyncer $rexOrderSyncer,
        ShopifyCustomerRepository $shopifyCustomerRepository,
        RexCustomerRepository $rexCustomerRepository,
        ShopifyOrderRepository $shopifyOrderRepository,
        ShopifyProductRepository $shopifyProductRepository,
        ShopifyVoucherFulfillmentMapper $shopifyVoucherFulfillmentMapper,
        ShopifyFulfillmentSyncer $shopifyFulfillmentSyncer,
        ShopifyFulfillmentRepository $shopifyFulfillmentRepository
    ) {
        $this->shopifySdkFactory = $shopifySdkFactory;
        $this->rexOrderMapperFromShopify = $rexOrderMapperFromShopify;
        $this->rexOrderRepository = $rexOrderRepository;
        $this->rexOrderSyncer = $rexOrderSyncer;
        $this->shopifyCustomerRepository = $shopifyCustomerRepository;
        $this->rexCustomerRepository = $rexCustomerRepository;
        $this->shopifyOrderRepository = $shopifyOrderRepository;
        $this->shopifyProductRepository = $shopifyProductRepository;
        $this->shopifyVoucherFulfillmentMapper = $shopifyVoucherFulfillmentMapper;
        $this->shopifyFulfillmentSyncer = $shopifyFulfillmentSyncer;
        $this->shopifyFulfillmentRepository = $shopifyFulfillmentRepository;
    }

    public function syncOut(ShopifyOrder $shopifyOrder, $shopifyOrderData = null)
    {
        SyncShopifyOrderOut::dispatch($shopifyOrder, $shopifyOrderData)
            ->onConnection('database_sync')
            ->onQueue('order');
    }

    public function syncIn(ShopifyOrder $shopifyOrder)
    {
        // todo
    }

    public function performSyncOut($shopifyOrderId, $shopifyOrderData = null)
    {
        $shopifyOrder = ShopifyOrder::findOrFail($shopifyOrderId);
        $shopifyStore = $shopifyOrder->shopifyStore;

        if (!isset($shopifyOrderData)) {
            $shopifyOrderData = $this->fetchShopifyOrderData($shopifyOrder);
        }

        foreach ($shopifyOrderData->line_items as $shopifyLineItemData) {
            if (null === $this->shopifyOrderRepository->getItem($shopifyOrder, $shopifyLineItemData->id)) {
                $this->shopifyOrderRepository->createItem($shopifyOrder, $shopifyLineItemData->id);
            }
        }

        foreach ($shopifyOrderData->fulfillments as $shopifyFulfillmentData) {
            $fulfillment = $this
                ->shopifyFulfillmentRepository
                ->getOrCreateByExternalId($shopifyOrder->id, $shopifyFulfillmentData->id);
            if ($shopifyFulfillmentData->status === 'success' && !$fulfillment->complete) {
                $fulfillment->complete = true;
                $fulfillment->save();
            }
            if ($shopifyFulfillmentData->status !== 'success' && $fulfillment->complete) {
                $this->shopifyFulfillmentSyncer->syncInCompletion($fulfillment);
            }
            foreach ($shopifyFulfillmentData->line_items as $fulfillmentItemData) {
                $orderItem = $this->shopifyOrderRepository->getItem($shopifyOrder, $fulfillmentItemData->id);
                $this->shopifyFulfillmentRepository
                    ->getOrCreateItem($fulfillment->id, $orderItem->id, $fulfillmentItemData->quantity);
            }
        }

        if (isset($shopifyOrderData->customer)) {
            $shopifyCustomerData = $shopifyOrderData->customer;
            $shopifyCustomer = $this->shopifyCustomerRepository->getOrCreate(
                $shopifyStore->id,
                $shopifyCustomerData->id
            );
            $shopifyCustomer->email = $shopifyOrderData->customer->email;
            $shopifyCustomer->save();
            if (!$shopifyCustomer->is($shopifyOrder->shopifyCustomer)) {
                $shopifyOrder->shopifyCustomer()->associate($shopifyCustomer);
                $shopifyOrder->save();
            }
            // Add validation to prevent duplicate customer error
             $rex_duplicate = $this->GetDuplicateRexCustomers($shopifyStore->rexSalesChannel->id,$shopifyCustomer->email);   
             if (count($rex_duplicate)>0)
             {
                \Artisan::call("shopify-connector:remove-duplicate-customers-by-email",
                    ['email'=>$shopifyCustomer->email,'saleschannel'=> $shopifyStore->rexSalesChannel->id]);

             }
             $shopify_duplicate = $this->GetDuplicateShopifyCustomers($shopifyStore->id,$shopifyCustomer->email); 
             if (count($shopify_duplicate)>0)
             {
                \Artisan::call("shopify-connector:remove-duplicate-customers-by-email",
                    ['email'=>$shopifyCustomer->email,'saleschannel'=> $shopifyStore->rexSalesChannel->id]);
             }
            // 

            $rexCustomer = $this->rexCustomerRepository->getOrCreateForShopifyCustomer($shopifyCustomer);
        } else {
            $rexCustomer = $this->rexCustomerRepository->create($shopifyStore->rexSalesChannel->id);
        }

        $rexOrder = $this->rexOrderRepository->getOrCreate($shopifyOrder, $rexCustomer);

        if (!$rexOrder->hasBeenSynced()) {
            $this->rexOrderSyncer->syncInFromShopify($rexOrder, $shopifyOrderData);
            $this->syncVoucherFulfillments($shopifyOrder, $shopifyOrderData);
        }
    }

    private function syncVoucherFulfillments(ShopifyOrder $shopifyOrder, $shopifyOrderData)
    {
        foreach ($shopifyOrderData->line_items as $lineItem) {
            $shopifyProductVariant = ShopifyProductVariant
                ::where('external_id', $lineItem->variant_id)
                ->with('rexProduct')
                ->first();
            if (isset($shopifyProductVariant) && $shopifyProductVariant->rexProduct->voucher_product == true) {
                $voucherFulfillment = ShopifyFulfillment
                    ::where('shopify_order_id', $shopifyOrder->id)
                    ->where('shopify_voucher_product_id', $shopifyProductVariant->shopifyProduct->id)
                    ->first();
                if (!isset($voucherFulfillment)) {
                    $voucherFulfillment = new ShopifyFulfillment;
                    $voucherFulfillment->shopify_order_id = $shopifyOrder->id;
                    $voucherFulfillment->shopify_voucher_product_id = $shopifyProductVariant->shopifyProduct->id;
                    $voucherFulfillment->save();
                }
                if (!$voucherFulfillment->hasBeenSynced()) {
                    $voucherFulfillmentData = $this->shopifyVoucherFulfillmentMapper
                        ->getMappedData($voucherFulfillment, $lineItem);
                    $this->shopifyFulfillmentSyncer->syncIn($voucherFulfillment, $voucherFulfillmentData);
                }
            }
        }
    }

    private function fetchShopifyOrderData(ShopifyOrder $shopifyOrder)
    {
        $shopifyStore = $shopifyOrder->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);
        $response = $shopifySdk->orders->read($shopifyOrder->external_id);
        $this->limitApiCalls($shopifyStore, $response->creditLeft());

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            $this->handleEntityNotfound($shopifyOrder);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }

        return $response->parsedResponse();
    }

    private function handleEntityNotfound(ShopifyOrder $shopifyOrder)
    {
        $shopifyStore = $shopifyOrder->shopifyStore;
        Log::error('Shopify order ' . $shopifyOrder->external_id
            . ' not found in Shopify store '
            . $shopifyStore->subdomain
            . '. Deleting.');
        $shopifyOrder->delete();
    }

    private function GetDuplicateRexCustomers($rex_sales_channel_id,$email)
    {
        return DB::table('rex_customers')
            ->where('rex_sales_channel_id' , $rex_sales_channel_id)
            ->where('email',$email)
            ->groupBy('email')
            ->having(DB::raw('count(email)'), '>', 1)
            ->pluck('email'); 
    }

    private function GetDuplicateShopifyCustomers($shopify_store_id,$email)
    {
        return DB::table('shopify_customers')
            ->where('shopify_store_id' , $shopify_store_id)
            ->where('email',$email)
            ->groupBy('email')
            ->having(DB::raw('count(email)'), '>', 1)
            ->pluck('email'); 
    }

}
