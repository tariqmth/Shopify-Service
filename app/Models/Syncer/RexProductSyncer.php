<?php

namespace App\Models\Syncer;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Attribute\AttributeRepository;
use App\Models\Client\Client;
use App\Models\Inventory\RexInventory;
use App\Models\Inventory\ShopifyInventoryItem;
use App\Models\Location\RexOutlet;
use App\Models\Mapper\ShopifyInventoryItemMapper;
use App\Models\Mapper\ShopifyInventoryLevelMapper;
use App\Models\Product\RexProduct;
use App\Models\Product\ShopifyProduct;
use App\Models\Product\ShopifyProductRepository;
use App\Models\Product\RexProductRepository;
use App\Models\Mapper\ShopifyProductMapper;
use App\Models\Store\RexSalesChannel;
use App\Models\Store\ShopifyStore;
use App\Packages\SkylinkSdkFactory;
use App\Queues\Jobs\ProcessRexEDSNotification;
use App\Queues\Jobs\SyncAllRexProductsOut;
use App\Queues\Jobs\SyncRexProductOut;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\Matrix;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\MatrixPolicyProductsNotConfiguredCorrectlyException;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\SimpleProduct;
use RetailExpress\SkyLink\Sdk\Apis\V2\Api as RexClient;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\V2ProductRepository as RexProductClient;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\V2ProductDeserializer as RexProductDeserializer;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\MatrixPolicyMapper;
use RetailExpress\SkyLink\Sdk\Outlets\OutletId;
use ValueObjects\Web\Url;
use ValueObjects\Identity\UUID as Uuid;
use ValueObjects\StringLiteral\StringLiteral;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\ProductNameAttribute;
use RetailExpress\SkyLink\Sdk\ValueObjects\SalesChannelId;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\ProductId;
use App\Models\Inventory\RexInventoryBufferGroupMapping;
use App\Models\Inventory\RexInventoryBufferGroup;
use App\Models\Mapper\Helper\InventoryBuffer;

class RexProductSyncer extends RexSyncer
{
    const MAX_JOBS = 1000;

    protected $shopifyMapper;
    protected $syncerRepository;
    protected $productRepository;
    protected $shopifyProductRepository;
    protected $attributeRepository;
    protected $shopifyInventoryItemMapper;
    protected $shopifyInventoryLevelMapper;
    protected $shopifyInventoryItemSyncer;
    protected $skylinkSdkFactory;
    protected $syncProductTypeIds =[];

    use InventoryBuffer;

    public function __construct(
        ShopifyProductMapper $shopifyProductMapper,
        SyncerRepository $syncerRepository,
        RexProductRepository $productRepository,
        ShopifyProductRepository $shopifyProductRepository,
        AttributeRepository $attributeRepository,
        ShopifyInventoryItemMapper $shopifyInventoryItemMapper,
        ShopifyInventoryLevelMapper $shopifyInventoryLevelMapper,
        ShopifyInventoryItemSyncer $shopifyInventoryItemSyncer,
        SkylinkSdkFactory $skylinkSdkFactory
    ) {
        $this->shopifyMapper = $shopifyProductMapper;
        $this->syncerRepository = $syncerRepository;
        $this->productRepository = $productRepository;
        $this->shopifyProductRepository = $shopifyProductRepository;
        $this->attributeRepository = $attributeRepository;
        $this->shopifyInventoryItemMapper = $shopifyInventoryItemMapper;
        $this->shopifyInventoryLevelMapper = $shopifyInventoryLevelMapper;
        $this->shopifyInventoryItemSyncer = $shopifyInventoryItemSyncer;
        $this->skylinkSdkFactory = $skylinkSdkFactory;
    }

    public function syncOut(RexProduct $rexProduct)
    {
        if (!$this->syncOutJobExists($rexProduct->id)) {
            SyncRexProductOut::dispatch($rexProduct)
                ->onConnection('database_sync')
                ->onQueue('product');
        }
    }
    public function syncInventoryBufferGroupByProductTypeId($buffer_group_id,$rexSalesChannelId,$productTypeIds)
    {
        $this->syncProductTypeIds = $productTypeIds;
        $this->syncInventoryBufferGroup($buffer_group_id,$rexSalesChannelId);
    }

    public function syncInventoryBufferGroup($buffer_group_id,$rexSalesChannelId)
    {

        $rexSalesChannel = RexSalesChannel::findOrFail($rexSalesChannelId);
        $client = $rexSalesChannel->client;
        $this->limitApiCalls($client);
        $productClient = $this->getRexProductClient($client);

        if (count($this->syncProductTypeIds)>0)
        {
            $rex_product_type_ids = $this->syncProductTypeIds; 
        }else{
            $rex_product_type_ids = RexInventoryBufferGroupMapping::
                where('group_id',$buffer_group_id)
                ->pluck('rex_product_type_id');
        }
        $product_ids = [];
        if (count($rex_product_type_ids)>0)
        {
            $rex_product_type_ids = is_array($rex_product_type_ids) ? $rex_product_type_ids : $rex_product_type_ids->toArray(); 
            foreach ($rex_product_type_ids as $type_id) {

                $result = $productClient->getProductIdsByProductTypeIdByChannel($type_id,$rexSalesChannel->external_id);

            }
        }
        $product_id_values = [];        
        $result_count = count($result);
        // Perform Product Sync
        if ($result_count > 0)
        {
            for ($i=0; $i < $result_count; $i++) 
            { 
                    $product_id_values[] = $result[$i]->toNative();
            }
        }
        // Get Rex Product Ids from shopify connector database
        $rex_products = RexProduct
            ::where('rex_sales_channel_id', $rexSalesChannelId)
            ->whereIn('external_id', $product_id_values)
            ->pluck('id');

        // Perform Product Sync

        foreach ($rex_products as $rex_product_id) {

            $this->performSyncOut($rex_product_id);
        }    
    }

    public function performSyncOut($rexProductId)
    {
        $product = RexProduct::findOrFail($rexProductId);
        $client = $product->rexSalesChannel->client;
        $this->limitApiCalls($client);
        $rexProductData = $this->fetchRexProductData($product);

        if (!isset($rexProductData)) {
            Log::notice('Product not found in Retail Express for this client and sales channel. '
                . 'It will be unpublished if already synced.', (array) $product);
            $this->removeFromGroup($product);
            $this->deactivateSimpleShopifyProduct($product);
            return;
        }

        if ($rexProductData->getId() !== null && $rexProductData->getId()->toNative() !== $product->external_id) {
            $product = $this->productRepository->create(
                $product->rexSalesChannel,
                $rexProductData->getId()->toNative()
            );
        }

        if ($rexProductData instanceof Matrix) {
            $this->syncOutMatrixProduct($product, $rexProductData);
        } else {
            $this->syncOutSimpleProduct($product, $rexProductData);
        }

        $product->voucher_product = $rexProductData->isVoucherProduct();
        $product->latest_version = $rexProductData->getLastUpdated();
        $product->save();
    }

    public function syncAllOut(RexSalesChannel $rexSalesChannel)
    {
        if (!$this->syncAllOutJobExists($rexSalesChannel->id)) {
            SyncAllRexProductsOut::dispatch($rexSalesChannel)
                ->onConnection('database_sync')
                ->onQueue('all_products');
        }
    }

    public function performSyncAllOut($rexSalesChannelId)
    {
        $rexSalesChannel = RexSalesChannel::findOrFail($rexSalesChannelId);
        $shopifyStore = $rexSalesChannel->shopifyStore;
        if (!isset($shopifyStore) || !$shopifyStore->enabled) {
            return;
        }
        $client = $rexSalesChannel->client;
        $this->limitApiCalls($client);

        $productIds = $this->fetchRexProductIds($rexSalesChannel);

        $count = count($productIds);
        if ($count > self::MAX_JOBS) {
            for ($i = 0; $i < $count; $i += self::MAX_JOBS) {
                $notification = new \stdClass();
                $notification->Type = 'Product';
                $notification->List = [];
                for ($id = $i; $id < $count && $id < $i + self::MAX_JOBS; $id++) {
                    $notification->List[] = $productIds[$id]->toNative();
                }
                ProcessRexEDSNotification::dispatch(
                    $rexSalesChannel->client->external_id,
                    $notification,
                    $rexSalesChannel->external_id)
                    ->onConnection('database_sync')
                    ->onQueue('notification');
            }
        } else {
            foreach ($productIds as $productId) {
                $product = $this->productRepository->create($rexSalesChannel, $productId->toNative());
                $this->syncOut($product);
            }
        }
    }

    public function syncIn(RexProduct $rexProduct, $rexProductData = null)
    {
        // todo
    }

    private function syncOutJobExists($productId)
    {
        $job = DB::table('sync_jobs')
            ->where('source', 'rex')
            ->where('queue', 'product')
            ->where('entity_id', $productId)
            ->where('direction', 'out')
            ->first();
        return isset($job);
    }

    private function syncAllOutJobExists($rexSalesChannelId)
    {
        $job = DB::table('sync_jobs')
            ->where('source', 'rex')
            ->where('queue', 'all_products')
            ->where('entity_id', $rexSalesChannelId)
            ->where('direction', 'out')
            ->first();
        return isset($job);
    }

    private function syncOutMatrixProduct(RexProduct $rexProduct, Matrix $rexGroupData)
    {
        $productsForGroup = array();
        $clientId = $rexProduct->rexSalesChannel->client->id;
        foreach ($rexGroupData->getProducts() as $rexProductData) {
            $this->attributeRepository->createAttributeOptionsFromProductData($clientId, $rexProductData);
            $productExternalId = $rexProductData->getId() ? $rexProductData->getId()->toNative() : null;
            $productSku = $rexProductData->getSku() ? $rexProductData->getSku()->toNative() : null;
            $productForGroup = $this->productRepository->create(
                $rexProduct->rexSalesChannel,
                $productExternalId,
                $productSku
            );
            
            // Update Rex Product
            $productForGroup->preorder_product = $rexProductData->isPreorderProduct();
            $productForGroup->available_stock = $rexProductData->getInventoryItem()->getQtyAvailable()->toInteger();
            $productForGroup->rex_product_type_id = $rexProductData->getProductType()->getId()->toNative();        
            $productForGroup->save();

            $productsForGroup[] = $productForGroup;
            if (isset($productForGroup->shopifyProduct)) {
                $this->shopifyProductRepository->setActiveStatus($productForGroup->shopifyProduct, false);
            }
        }
        $productGroup = $this->productRepository->createGroup($rexGroupData->getManufacturerSku(), $productsForGroup);
        $shopifyProduct = $this->shopifyProductRepository->getOrCreateForRexProductGroup($productGroup);
        $this->updateShopifyProductTitle($shopifyProduct, $rexGroupData);
        $this->shopifyProductRepository->setActiveStatus($shopifyProduct, true);
        $mappedData = $this->shopifyMapper->getMappedDataFromGroup($productGroup, $shopifyProduct, $rexGroupData);
        $syncer = $this->syncerRepository->getSyncer($shopifyProduct);
        $syncer->syncIn($shopifyProduct, $mappedData);

        if (!$rexGroupData->isVoucherProduct()) {
            foreach ($productGroup->rexProducts as $childRexProduct) {
                $rexProductData = $rexGroupData->getProduct(ProductId::fromNative($childRexProduct->external_id));
                $shopifyProductVariant = $this->shopifyProductRepository->getVariant(
                    $childRexProduct->id,
                    $shopifyProduct->id
                );
                $shopifyInventoryItem = $shopifyProductVariant->shopifyInventoryItem;
                if (!isset($shopifyInventoryItem)) {
                    $this->shopifyProductRepository->getOrCreateInventoryItem($shopifyProductVariant);
                } elseif (isset($shopifyInventoryItem->external_id)) {
                    $this->syncOutInventory($shopifyInventoryItem, $rexProductData, $childRexProduct);
                }
            }
        }
    }

    private function syncOutSimpleProduct(RexProduct $rexProduct, SimpleProduct $rexProductData)
    {
        $productSku = $rexProductData->getSku() ? $rexProductData->getSku()->toNative() : null;
        if ($rexProduct->sku !== $productSku) {
            $rexProduct->sku = $productSku;
            $rexProduct->save();
        }
        // Update Rex Product
        $rexProduct->preorder_product = $rexProductData->isPreorderProduct();
        $rexProduct->available_stock = $rexProductData->getInventoryItem()->getQtyAvailable()->toInteger();
        $rexProduct->rex_product_type_id = $rexProductData->getProductType()->getId()->toNative();        
        $rexProduct->save();

        $clientId = $rexProduct->rexSalesChannel->client->id;
        $this->attributeRepository->createAttributeOptionsFromProductData($clientId, $rexProductData);
        if ($rexProduct->belongsToGroup()) {
            $this->productRepository->dissociateFromGroup($rexProduct);
        }

        $shopifyProduct = $this->shopifyProductRepository->getOrCreate($rexProduct);
        $this->updateShopifyProductTitle($shopifyProduct, $rexProductData);
        $this->shopifyProductRepository->setActiveStatus($shopifyProduct, true);
        $syncer = $this->syncerRepository->getSyncer($shopifyProduct);
        $mappedData = $this->shopifyMapper->getMappedData($rexProduct, $shopifyProduct, $rexProductData);
        $syncer->syncIn($shopifyProduct, $mappedData);

        if (!$rexProductData->isVoucherProduct()) {
            $shopifyProductVariant = $this->shopifyProductRepository->getVariant($rexProduct->id, $shopifyProduct->id);
            $shopifyInventoryItem = $shopifyProductVariant->shopifyInventoryItem;
            if (!isset($shopifyInventoryItem)) {
                $this->shopifyProductRepository->getOrCreateInventoryItem($shopifyProductVariant);
            } elseif (isset($shopifyInventoryItem->external_id)) {
                $this->syncOutInventory($shopifyInventoryItem, $rexProductData, $rexProduct);
            }
        }
    }

    private function updateShopifyProductTitle(ShopifyProduct $shopifyProduct, $rexData)
    {
        if ($shopifyProduct->title !== $rexData->getName()->toNative()) {
            $shopifyProduct->title = $rexData->getName()->toNative();
            $shopifyProduct->save();
        }
    }

    private function syncOutInventory(
        ShopifyInventoryItem $shopifyInventoryItem,
        SimpleProduct $rexProductData,
        RexProduct $rexProduct
    ) {
        $updatedOutletQtyData = 0;
        $product_type_id = $rexProductData->getProductType()->getId()->toNative();

        foreach ($rexProductData->getInventoryItem()->getOutletQtys() as $outletQtyData) {
            $rexOutlet = $rexProduct
                ->rexSalesChannel
                ->rexOutlets
                ->where('external_id', $outletQtyData->getOutletId()->toNative())
                ->first();
            if (!isset($rexOutlet)) {
                $rexOutlet = new RexOutlet;
                $rexOutlet->rex_sales_channel_id = $rexProduct->rex_sales_channel_id;
                $rexOutlet->external_id = $outletQtyData->getOutletId()->toNative();
                $rexOutlet->save();
            }
            $rexInventory = RexInventory
                ::where('rex_product_id', $rexProduct->id)
                ->where('rex_outlet_id', $rexOutlet->id)
                ->first();
            if (!isset($rexInventory)) {
                $rexInventory = new RexInventory;
                $rexInventory->rexOutlet()->associate($rexOutlet);
                $rexInventory->rexProduct()->associate($rexProduct);
            }
            $rexInventory->quantity = $rexProductData
                ->getInventoryItem()
                ->getOutletQty(new OutletId($rexOutlet->external_id))
                ->getQty()
                ->toInteger();


            // Calling trait InventoryBuffer to get inventory buffer quantity value

            $inventory_buffer = $this->getInventoryBufferQty($product_type_id,$rexOutlet->rex_sales_channel_id);

            // When syncing the inventory level, rather than just syncing the current available for the product,
            // sync [current available] - [buffer] from above 


            if ($inventory_buffer > 0)
            {     
                $updatedOutletQtyData = $inventory_buffer;
            }


            // Update existing rexdata
            $rexInventory->save();

        }
        $mappedInventoryItemData = $this->shopifyInventoryItemMapper->getMappedData($rexProductData);
        $this->shopifyInventoryItemSyncer->syncIn($shopifyInventoryItem, $mappedInventoryItemData);
        $shopifyLocation = $shopifyInventoryItem
            ->shopifyProductVariant
            ->shopifyProduct
            ->shopifyStore
            ->shopifyFulfillmentService
            ->shopifyLocation;
        $mappedInventoryLevelData = $this->shopifyInventoryLevelMapper->getMappedData(
            $shopifyInventoryItem,
            $shopifyLocation,
            $rexProductData,
            $updatedOutletQtyData   
        );
        $this->shopifyInventoryItemSyncer->syncInLevel($shopifyInventoryItem, $mappedInventoryLevelData);
    }

    private function fetchRexProductData(RexProduct $rexProduct)
    {
        $salesChannel = $rexProduct->rexSalesChannel;
        $client = $salesChannel->client;
        $productClient = $this->getRexProductClient($client);
        $productId = ProductId::fromNative($rexProduct->external_id);
        $salesChannelId = SalesChannelId::fromNative($salesChannel->external_id);

        try {
            $productData = $productClient->find($productId, $salesChannelId);
        } catch (MatrixPolicyProductsNotConfiguredCorrectlyException $e) {
            Log::warning('Matrix not configured correctly, disabling products with all associated IDs.',
                $e->getMatrixProductIds());
            foreach ($e->getMatrixProductIds() as $matrixProductId) {
                $siblingProduct = $this->productRepository->getByExternalId(
                    $salesChannel->id,
                    $matrixProductId->toNative()
                );
                if (isset($siblingProduct)) {
                    $this->deactivateMatrixShopifyProduct($siblingProduct);
                    $this->deactivateSimpleShopifyProduct($siblingProduct);
                }
            }
            throw new ImpossibleTaskException('Matrix not configured correctly, cannot continue.', 0, $e);
        }

        return $productData;
    }

    private function fetchRexProductIds(RexSalesChannel $rexSalesChannel)
    {
        $client = $rexSalesChannel->client;
        $salesChannelId = SalesChannelId::fromNative($rexSalesChannel->external_id);
        $productClient = $this->getRexProductClient($client);
        return $productClient->allIds($salesChannelId);
    }

    private function getRexProductClient(Client $client)
    {
        $api = $this->skylinkSdkFactory->getApi($client);
        $deserializer = new RexProductDeserializer(ProductNameAttribute::get('description'));
        $matrixPolicyMapper = new MatrixPolicyMapper();
        return new RexProductClient($matrixPolicyMapper, $deserializer, $api);
    }

    private function deactivateMatrixShopifyProduct(RexProduct $rexProduct)
    {
        if (isset($rexProduct->rexProductGroup) && isset($rexProduct->rexProductGroup->shopifyProduct)) {
            $shopifyProduct = $rexProduct->rexProductGroup->shopifyProduct;
            $this->shopifyProductRepository->setActiveStatus($shopifyProduct, false);
        }
    }

    private function removeFromGroup(RexProduct $rexProduct)
    {
        if (isset($rexProduct->rexProductGroup)) {
            $this->productRepository->dissociateFromGroup($rexProduct);
        }
    }

    private function deactivateSimpleShopifyProduct(RexProduct $rexProduct)
    {
        if (isset($rexProduct->shopifyProduct)) {
            $shopifyProduct = $rexProduct->shopifyProduct;
            $this->shopifyProductRepository->setActiveStatus($shopifyProduct, false);
        }
    }
}
