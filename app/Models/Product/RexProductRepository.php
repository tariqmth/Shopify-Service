<?php

namespace App\Models\Product;

use App\Models\Mapper\DisabledShopifyProductMapper;
use App\Models\Store\RexSalesChannel;
use App\Models\Store\ShopifyStore;
use App\Models\SyncableRepository;
use App\Models\Syncer\SyncerRepository;

class RexProductRepository extends SyncableRepository
{
    protected $syncerRepository;
    protected $shopifyProductRepository;

    public function __construct(
        SyncerRepository $syncerRepository,
        ShopifyProductRepository $shopifyProductRepository
    ) {
        $this->syncerRepository = $syncerRepository;
        $this->shopifyProductRepository = $shopifyProductRepository;
    }

    public function create(RexSalesChannel $salesChannel, $externalId, $sku = null)
    {
        $product = RexProduct
            ::where('rex_sales_channel_id', $salesChannel->id)
            ->where('external_id', $externalId)
            ->first();

        if (!isset($product)) {
            $product = new RexProduct;
            $product->rex_sales_channel_id = $salesChannel->id;
            $product->external_id = $externalId;
            $product->sku = $sku;
            $product->save();
        }

        if ($sku !== null && $sku !== $product->sku) {
            $product->sku = $sku;
            $product->save();
        }

        return $product;
    }

    public function createGroup($groupCode, array $products)
    {
        $firstProduct = reset($products);
        $rexSalesChannel = $firstProduct->rexSalesChannel;
        $productGroup = RexProductGroup::firstOrCreate([
            'rex_sales_channel_id' => $rexSalesChannel->id,
            'code'         => $groupCode
        ]);

        $relationshipsUpdated = false;

        foreach ($productGroup->rexProducts as $existingProduct) {
            if (!$this->arrayContainsProduct($products, $existingProduct)) {
                $this->dissociateFromGroup($existingProduct);
                $relationshipsUpdated = true;
            }
        }

        $latestVersion = null;

        foreach ($products as $product) {
            $oldProductGroup = $product->rexProductGroup;
            if (!$productGroup->is($oldProductGroup)) {
                if (isset($oldProductGroup)) {
                    $this->dissociateFromGroup($product);
                }
                $product->rexProductGroup()->associate($productGroup);
                $product->save();
                $relationshipsUpdated = true;
            }
            if (!isset($latestVersion) || $latestVersion < $product->latest_version) {
                $latestVersion = $product->latest_version;
            }
        }

        if ($productGroup->latest_version !== $latestVersion) {
            $productGroup->latest_version = $latestVersion;
            $productGroup->save();
        }

        if ($relationshipsUpdated) {
            $productGroup->load('rexProducts');
        }

        return $productGroup;
    }

    private function arrayContainsProduct($haystack, $needle)
    {
        foreach ($haystack as $product) {
            if ($product->is($needle)) {
                return true;
            }
        }

        return false;
    }

    public function dissociateFromGroup(RexProduct $rexProduct)
    {
        if (!$rexProductGroup = $rexProduct->rexProductGroup) {
            return;
        }
        $rexProduct->rexProductGroup()->dissociate();
        $rexProduct->save();
        // If the product group has been synced to Shopify and has <= 1 products left, disable the grouped product in
        // Shopify and sync out the rex product again to sync as a simple product with no variants
        $shopifyProductOfGroup = $rexProductGroup->shopifyProduct;
        if (isset($shopifyProductOfGroup) &&  $rexProductGroup->rexProducts()->count() <= 1) {
            $rexProduct = $rexProductGroup->rexProducts->first();
            if (isset($rexProduct)) {
                $syncer = $this->syncerRepository->getSyncer($rexProduct);
                $syncer->syncOut($rexProduct);
            }
            $this->shopifyProductRepository->setActiveStatus($shopifyProductOfGroup, false);
        }
    }

    public function getAllForShopifyProduct(ShopifyProduct $shopifyProduct)
    {
        $rexProducts = [];
        if (isset($shopifyProduct->rexProduct)) {
            $rexProducts[] = $shopifyProduct->rexProduct;
        } elseif (isset($shopifyProduct->rexProductGroup) && isset($shopifyProduct->rexProductGroup->rexProducts)) {
            $rexProducts = $shopifyProduct->rexProductGroup->rexProducts->all();
        }
        return $rexProducts;
    }

    public function getFirstForShopifyProduct(ShopifyProduct $shopifyProduct)
    {
        $rexProducts = $this->getAllForShopifyProduct($shopifyProduct);
        return count($rexProducts) ? reset($rexProducts) : null;
    }

    public function getByExternalId($rexSalesChannelId, $rexProductExternalId)
    {
        return RexProduct
            ::where('rex_sales_channel_id', $rexSalesChannelId)
            ->where('external_id', $rexProductExternalId)
            ->first();
    }
}
