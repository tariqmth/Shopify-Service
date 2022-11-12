<?php

namespace App\Models\Product;

use App\Models\Inventory\ShopifyInventoryItem;
use App\Models\Store\ShopifyStore;
use App\Models\Syncer\SyncerRepository;
use Shopify\ShopifyVariant;

class ShopifyProductRepository
{
    protected $syncerRepository;
    protected $disabledShopifyProductMapper;

    public function __construct(
        SyncerRepository $syncerRepository
    ) {
        $this->syncerRepository = $syncerRepository;
    }

    public function getByExternalId(ShopifyStore $shopifyStore, $externalId)
    {
        return ShopifyProduct
            ::where('shopify_store_id', $shopifyStore->id)
            ->where('external_id', $externalId)
            ->first();
    }

    public function createByExternalId(ShopifyStore $shopifyStore, $externalId, $title = null)
    {
        $shopifyProduct = new ShopifyProduct;
        $shopifyProduct->shopifyStore()->associate($shopifyStore);
        $shopifyProduct->external_id = $externalId;
        $shopifyProduct->title = $title;
        $shopifyProduct->save();
        return $shopifyProduct;
    }

    public function getOrCreate(RexProduct $rexProduct)
    {
        if (!$shopifyStore = $rexProduct->rexSalesChannel->shopifyStore) {
            throw new \Exception('Can not create Shopify product for Rex channel with no Shopify store.');
        }

        $shopifyProduct = ShopifyProduct
            ::where('shopify_store_id', $shopifyStore->id)
            ->where('rex_product_id', $rexProduct->id)
            ->first();
        if (!isset($shopifyProduct) && isset($rexProduct->sku)) {
            $shopifyProduct = $this->setForRexProductBySku($rexProduct, $shopifyStore);
        }
        if (!isset($shopifyProduct)) {
            $shopifyProduct = new ShopifyProduct;
            $shopifyProduct->shopify_store_id = $shopifyStore->id;
            $shopifyProduct->rex_product_id = $rexProduct->id;
            $shopifyProduct->save();
        }

        $this->getOrCreateVariant($rexProduct, $shopifyProduct);

        return $shopifyProduct;
    }

    public function getOrCreateForRexProductGroup(RexProductGroup $rexProductGroup)
    {
        if (!$shopifyStore = $rexProductGroup->rexSalesChannel->shopifyStore) {
            throw new \Exception('Can not create Shopify product for Rex channel with no Shopify store.');
        }

        $rexProducts = $rexProductGroup->rexProducts;

        $shopifyProduct = ShopifyProduct
            ::where('shopify_store_id', $shopifyStore->id)
            ->where('rex_product_group_id', $rexProductGroup->id)
            ->first();
        if (!isset($shopifyProduct)) {
            $shopifyProduct = $this->setForRexProductGroupBySku($rexProductGroup, $shopifyStore);
        }
        if (!isset($shopifyProduct)) {
            $shopifyProduct = new ShopifyProduct;
            $shopifyProduct->shopify_store_id = $shopifyStore->id;
            $shopifyProduct->rex_product_group_id = $rexProductGroup->id;
            $shopifyProduct->save();
        }

        foreach ($rexProducts as $rexProduct) {
            $this->getOrCreateVariant($rexProduct, $shopifyProduct);
        }

        return $shopifyProduct;
    }

    public function getVariantByExternalId(ShopifyProduct $shopifyProduct, $externalId)
    {
        return ShopifyProductVariant
            ::where('shopify_product_id', $shopifyProduct->id)
            ->where('external_id', $externalId)
            ->orderBy('deleted', 'asc')
            ->first();
    }

    public function createVariantByExternalId(ShopifyProduct $shopifyProduct, $externalId, $sku = null)
    {
        $variant = new ShopifyProductVariant;
        $variant->shopifyProduct()->associate($shopifyProduct);
        $variant->external_id = $externalId;
        $variant->sku = $sku;
        $variant->save();
        return $variant;
    }

    public function getVariant($rexProductId, $shopifyProductId)
    {
        $variant = ShopifyProductVariant
            ::where('shopify_product_id', $shopifyProductId)
            ->where('rex_product_id', $rexProductId)
            ->where(function($query) {
                $query->where('deleted', false)
                    ->orWhereNull('deleted');
            })->first();
        return $variant;
    }

    public function getOrCreateVariant(RexProduct $rexProduct, ShopifyProduct $shopifyProduct)
    {
        $shopifyProductVariant = $this->getVariant($rexProduct->id, $shopifyProduct->id);

        if (!isset($shopifyProductVariant)) {
            foreach ($shopifyProduct->shopifyProductVariants as $possibleMatchingVariant) {
                if (isset($possibleMatchingVariant->sku)
                    && $possibleMatchingVariant->sku === $rexProduct->sku
                    && $possibleMatchingVariant->rex_product_id === null
                ) {
                    $shopifyProductVariant = $possibleMatchingVariant;
                    $shopifyProductVariant->rexProduct()->associate($rexProduct);
                    $shopifyProductVariant->save();
                    break;
                }
            }
        }

        if (!isset($shopifyProductVariant)) {
            if (!$rexProduct->isAssociatedWith($shopifyProduct)) {
                throw new \Exception('Cannot create variant for unrelated products.');
            }
            $shopifyProductVariant = new ShopifyProductVariant;
            $shopifyProductVariant->shopify_product_id = $shopifyProduct->id;
            $shopifyProductVariant->rex_product_id = $rexProduct->id;
            $shopifyProductVariant->deleted = false;
            $shopifyProductVariant->sku = $rexProduct->sku;
            $shopifyProductVariant->save();
            if (!$shopifyProductVariant->is($this->getVariant($rexProduct->id, $shopifyProduct->id))) {
                $shopifyProductVariant->delete();
                throw new \Exception('Possible duplicate Shopify variant created ('
                    . $shopifyProductVariant->id . '). Deleting.');
            }
        }

        return $shopifyProductVariant;
    }

    public function getOrCreateInventoryItem(ShopifyProductVariant $shopifyProductVariant)
    {
        $shopifyInventoyItem = $shopifyProductVariant->shopifyInventoryItem;

        if (!isset($shopifyInventoyItem)) {
            $shopifyInventoyItem = new ShopifyInventoryItem;
            $shopifyInventoyItem->shopifyProductVariant()->associate($shopifyProductVariant);
            $shopifyInventoyItem->save();
        }

        return $shopifyInventoyItem;
    }

    public function setActiveStatus(ShopifyProduct $shopifyProduct, $activeStatus = true)
    {
        if ($shopifyProduct->active != $activeStatus) {
            $shopifyProduct->active = $activeStatus;
            $shopifyProduct->save();
            if ($shopifyProduct->hasBeenSynced()) {
                $shopifySyncer = $this->syncerRepository->getSyncer($shopifyProduct);
                $shopifySyncer->syncActiveStatus($shopifyProduct);
            }
        }
    }

    private function setForRexProductBySku(RexProduct $rexProduct, ShopifyStore $shopifyStore) {
        $shopifyVariant = $this->getUnlinkedVariantBySku($rexProduct->sku, $shopifyStore);
        if (isset($shopifyVariant)) {
            $shopifyProduct = $shopifyVariant->shopifyProduct;
            $shopifyProduct->rex_product_id = $rexProduct->id;
            $shopifyProduct->save();
            return $shopifyProduct;
        }
    }

    private function setForRexProductGroupBySku(RexProductGroup $rexProductGroup, ShopifyStore $shopifyStore) {
        $firstRexProduct = $rexProductGroup->rexProducts()->first();
        if (!isset($firstRexProduct)) {
            return null;
        }
        $shopifyVariant = $this->getUnlinkedVariantBySku($firstRexProduct->sku, $shopifyStore);
        if (isset($shopifyVariant)) {
            $shopifyProduct = $shopifyVariant->shopifyProduct;
            $shopifyProduct->rex_product_group_id = $rexProductGroup->id;
            $shopifyProduct->save();
            return $shopifyProduct;
        }
    }

    private function getUnlinkedVariantBySku($sku, ShopifyStore $shopifyStore)
    {
        return ShopifyProductVariant
            ::where('sku', $sku)
            ->whereNull('rex_product_id')
            ->where(function ($query) {
                $query->where('deleted', false)
                    ->orWhereNull('deleted');
            })->whereHas('ShopifyProduct', function ($query) use ($shopifyStore) {
                $query->where('shopify_store_id', $shopifyStore->id)
                    ->whereNull('rex_product_id')
                    ->whereNull('rex_product_group_id');
            })->first();
    }
}