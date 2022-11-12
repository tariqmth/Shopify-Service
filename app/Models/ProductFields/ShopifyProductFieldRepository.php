<?php

namespace App\Models\ProductFields;

use Illuminate\Support\Collection;

class ShopifyProductFieldRepository
{
    public function initialize()
    {
        $titleField = $this->createOrUpdate('title', 'Product Title');
        $this->createMapping(null, $titleField->id, 'Description');
    }

    public function get($name)
    {
        return ShopifyProductField::where('name', $name)->first();
    }

    public function getAll()
    {
        return ShopifyProductField::all();
    }

    public function createOrUpdate($name, $label)
    {
        $shopifyProductField = $this->get($name);

        if ($shopifyProductField === null) {
            $shopifyProductField = new ShopifyProductField;
            $shopifyProductField->name = $name;
        }

        $shopifyProductField->label = $label;
        $shopifyProductField->save();

        return $shopifyProductField;
    }

    public function createOrUpdateMapping($shopifyStoreId, $shopifyProductFieldId, $rexProductFieldName = null)
    {
        $mapping = $this->getMapping($shopifyStoreId, $shopifyProductFieldId);

        if (!isset($mapping)) {
            $mapping = new ShopifyProductFieldMapping;
            $mapping->shopify_store_id = $shopifyStoreId;
            $mapping->shopify_product_field_id = $shopifyProductFieldId;
        }

        $mapping->rex_product_field_name = $rexProductFieldName;
        $mapping->save();

        return $mapping;
    }

    public function getMapping($shopifyStoreId, $shopifyProductFieldId, $withDefaults = false)
    {
        $mapping = ShopifyProductFieldMapping
            ::where('shopify_store_id', $shopifyStoreId)
            ->where('shopify_product_field_id', $shopifyProductFieldId)
            ->first();

        if (!isset($mapping) && $withDefaults) {
            $mapping = ShopifyProductFieldMapping
                ::whereNull('shopify_store_id')
                ->where('shopify_product_field_id', $shopifyProductFieldId)
                ->first();
        }

        return $mapping;
    }

    public function getMappings($shopifyStoreId, $withDefaults = false)
    {
        $mappings = ShopifyProductFieldMapping
            ::where('shopify_store_id', $shopifyStoreId)
            ->with('shopifyProductField')
            ->get();

        if ($withDefaults) {
            $defaultMappings = ShopifyProductFieldMapping
                ::whereNull('shopify_store_id')
                ->with('shopifyProductField')
                ->get();
            foreach ($defaultMappings as $defaultMapping) {
                $defaultMappingFieldId = $defaultMapping->shopify_product_field_id;
                if ($this->getMappingFromCollection($mappings, $defaultMappingFieldId) === null) {
                    $mappings->push($defaultMapping);
                }
            }
        }

        return $mappings;
    }

    private function getMappingFromCollection(Collection $mappingCollection, $shopifyProductFieldId)
    {
        return $mappingCollection->where('shopify_product_field_id', $shopifyProductFieldId)->first();
    }
}