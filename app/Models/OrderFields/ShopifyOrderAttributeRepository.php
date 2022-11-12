<?php

namespace App\Models\OrderFields;

use Illuminate\Support\Collection;

class ShopifyOrderAttributeRepository
{
    public function createOrUpdateMapping($shopifyStoreId, $shopifyOrderAttribute, $rexOrderField)
    {
        $mapping = $this->getMapping($shopifyStoreId, $shopifyOrderAttribute);

        if (!isset($mapping)) {
            $mapping = new ShopifyOrderAttributeMapping;
            $mapping->shopify_store_id = $shopifyStoreId;
            $mapping->shopify_order_attribute = $shopifyOrderAttribute;
        }

        $mapping->rex_order_field = $rexOrderField;
        $mapping->save();

        return $mapping;
    }

    public function getMapping($shopifyStoreId, $shopifyOrderAttribute)
    {
        $mapping = ShopifyOrderAttributeMapping
            ::where('shopify_store_id', $shopifyStoreId)
            ->where('shopify_order_attribute', $shopifyOrderAttribute)
            ->first();

        return $mapping;
    }

    public function getMappings($shopifyStoreId)
    {
        $mappings = ShopifyOrderAttributeMapping::where('shopify_store_id', $shopifyStoreId)->get();

        return $mappings;
    }
}