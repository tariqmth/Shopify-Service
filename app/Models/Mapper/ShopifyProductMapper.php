<?php

namespace App\Models\Mapper;

use App\Models\Attribute\AttributeRepository;
use App\Models\Attribute\RexAttribute;
use App\Models\Attribute\RexAttributeOption;
use App\Models\Product\RexProduct;
use App\Models\Product\RexProductGroup;
use App\Models\Product\ShopifyProduct;
use App\Models\Product\ShopifyProductRepository;
use App\Models\ProductFields\ShopifyProductFieldRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RetailExpress\SkyLink\Sdk\Catalogue\Attributes\AttributeCode;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\SimpleProduct as RexData;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\Matrix as RexGroupData;
use App\Models\Mapper\Helper\InventoryBuffer;
use App\Models\Store\ShopifyStore;

class ShopifyProductMapper extends Mapper
{
    protected $shopifyProductRepository;
    protected $shopifyProductFieldRepository;
    protected $shopify_store_id;
    protected $rex_sales_channel_id;
    use InventoryBuffer;

    public function __construct(
        ShopifyProductRepository $shopifyProductRepository,
        ShopifyProductFieldRepository $shopifyProductFieldRepository
    ) {
        $this->shopifyProductRepository = $shopifyProductRepository;
        $this->shopifyProductFieldRepository = $shopifyProductFieldRepository;
    }

    public function getMappedData(RexProduct $rexProduct, ShopifyProduct $shopifyProduct, RexData $rexData)
    {
        $this->shopify_store_id = $shopifyProduct->shopify_store_id;
        $this->rex_sales_channel_id = $rexProduct->rex_sales_channel_id;
        $mappings = $this->shopifyProductFieldRepository->getMappings($this->shopify_store_id, true);

        $slaveData = [
            'title'                       => $this->getTitle($rexData, $mappings),
            'product_type'                => $this->getProductType($rexData),
            'vendor'                      => $this->getVendor($rexData),
            'variants'                    => $this->getVariants($rexProduct, $shopifyProduct, $rexData, $mappings),
            'options'                     => $this->getOptions($rexData),
            'version'                     => $this->getVersion($rexData)
            /*
             * Tags have been temporarily disabled until a solution is developed that will allow
             * Shopify store owners to keep their existing tags without being overwritten.
             *
             * 'tags'                        => $this->getTags($rexData)
             */
        ];

        $metafieldGlobalTitleTag = $this->getMetafieldGlobalTitleTag($rexData, $mappings);
        if (isset($metafieldGlobalTitleTag)) {
            $slaveData['metafields_global_title_tag'] = $metafieldGlobalTitleTag;
        }

        return $slaveData;
    }

    public function getMappedDataFromGroup(
        RexProductGroup $rexProductGroup,
        ShopifyProduct $shopifyProduct,
        RexGroupData $rexData
    ) {
        $representativeProduct = $rexData->getRepresentativeProduct();
        $this->shopify_store_id = $shopifyProduct->shopify_store_id;
        $this->rex_sales_channel_id = $rexProductGroup->rexSalesChannel->rex_sales_channel_id;
        $mappings = $this->shopifyProductFieldRepository->getMappings($this->shopify_store_id, true);

        $slaveData = [
            'title'                       => $this->getTitle($representativeProduct, $mappings),
            'product_type'                => $this->getProductType($representativeProduct),
            'vendor'                      => $this->getVendor($representativeProduct),
            'variants'                    => $this->getVariantsFromGroup($rexProductGroup, $shopifyProduct, $rexData, $mappings),
            'options'                     => $this->getOptionsFromGroup($rexData),
            'version'                     => $this->getVersion($representativeProduct)
            /*
             * 'tags'                        => $this->getTagsFromGroup($rexData)
             */
        ];

        $metafieldGlobalTitleTag = $this->getMetafieldGlobalTitleTag($representativeProduct, $mappings);
        if (isset($metafieldGlobalTitleTag)) {
            $slaveData['metafields_global_title_tag'] = $metafieldGlobalTitleTag;
        }

        return $slaveData;
    }

    protected function getVariantsFromGroup(
        RexProductGroup $rexProductGroup,
        ShopifyProduct $shopifyProduct,
        RexGroupData $rexProductGroupData,
        Collection $mappings
    ) {
        $variants = array();

        $rexProductsData = $rexProductGroupData->getProducts();

        $attributeCodesForSorting = [
            AttributeCode::fromNative(AttributeCode::COLOUR),
            AttributeCode::fromNative(AttributeCode::SIZE)
        ];

        foreach ($attributeCodesForSorting as $attributeCodeForSorting) {
            if ($this->canSortProductsByOption($rexProductsData, $attributeCodeForSorting)) {
                $rexProductsData = $this->getProductsSortedByOption($rexProductsData, $attributeCodeForSorting);
            }
        }

        foreach ($rexProductsData as $rexProductData) {
            $rexProduct = $rexProductGroup->getRexProductByExternalId($rexProductData->getId()->toNative());
            $variants[] = $this->getVariant($rexProduct, $shopifyProduct, $rexProductData, $mappings);
        }
        return $variants;
    }

    protected function getOptionsFromGroup(RexGroupData $rexData)
    {
        $attributeCodes = $rexData->getPolicy()->getAttributeCodes();
        $options = array();
        foreach ($attributeCodes as $attributeCode) {
            $options[] = $this->getOptionFromGroup($rexData, $attributeCode);
        }
        $options = array_filter($options, function($option) {
            return !is_null($option);
        });
        $options = array_values($options);
        return $options;
    }

    protected function getOptionFromGroup(RexGroupData $rexData, AttributeCode $attributeCode)
    {
        $values = array();
        $productsData = $rexData->getProducts();

        if ($this->canSortProductsByOption($productsData, $attributeCode)) {
            $productsData = $this->getProductsSortedByOption($productsData, $attributeCode);
        }

        foreach ($productsData as $productData) {
            $value = $this->getAttributeValue($productData, $attributeCode->getValue());
            if (!is_null($value)) {
                $values[] = $value;
            }
        }

        $values = array_unique($values);
        $values = array_values($values);

        if (count($values) > 0) {
            return [
                'name' => $attributeCode->getLabel()->toNative(),
                'values' => $values
            ];
        } else {
            return null;
        }
    }

    protected function getAttributeValue(RexData $rexData, $attributeName)
    {
        $optionData = $rexData->getAttributeOption(AttributeCode::fromNative($attributeName));
        if (isset($optionData) && $optionData->getLabel() !== null && $optionData->getLabel() !== '') {
            return $optionData->getLabel()->toNative();
        }
    }

    protected function getProductsSortedByOption(array $rexProductsData, AttributeCode $attributeCode)
    {
        usort($rexProductsData, function($productDataA, $productDataB) use ($attributeCode) {
            $optionDataA = $productDataA->getAttributeOption($attributeCode);
            $optionDataB = $productDataB->getAttributeOption($attributeCode);
            if ($optionDataA->getSortOrder() !== null && $optionDataB->getSortOrder() !== null) {
                return $optionDataA->getSortOrder() <=> $optionDataB->getSortOrder();
            } else {
                $optionValueA = $this->getAttributeValue($productDataA, $attributeCode->getValue());
                $optionValueB = $this->getAttributeValue($productDataB, $attributeCode->getValue());
                return strcmp($optionValueA, $optionValueB);
            }
        });

        return $rexProductsData;
    }

    protected function canSortProductsByOption(array $rexProductsData, AttributeCode $attributeCode)
    {
        foreach ($rexProductsData as $rexProductData) {
            if ($rexProductData->getAttributeOption($attributeCode) === null) {
                return false;
            }
        }
        return true;
    }

    protected function getTitle(RexData $rexData, Collection $mappings)
    {
        $mappedValue = $this->getMappedFieldValue($rexData, $mappings, 'title');

        return $mappedValue ?? $rexData->getName()->toNative();
    }

    protected function getMappedFieldValue(RexData $rexData, Collection $mappings, $shopifyFieldName)
    {
        $mapping = $mappings->filter(function($mapping) use ($shopifyFieldName) {
           return $mapping->shopifyProductField->name === $shopifyFieldName;
        })->first();

        if (isset($mapping)) {
            if ( $mapping->rex_product_field_name == 'off' ) {
                return $mapping->rex_product_field_name;
            } else {
                $rexField = $mapping->rex_product_field_name;
                return $rexData->getField($rexField);
            }
        }

        return null;
    }

    protected function getMetafieldGlobalTitleTag(RexData $rexData, Collection $mappings)
    {
        $mappedValue = $this->getMappedFieldValue($rexData, $mappings, 'metafields_global_title_tag');

        return $mappedValue;
    }

    protected function getProductType(RexData $rexData)
    {
        return $this->getAttributeValue($rexData, 'product_type');
    }

    protected function getVendor(RexData $rexData)
    {
        return $this->getAttributeValue($rexData, 'brand');
    }

    protected function getVariants(RexProduct $rexProduct, ShopifyProduct $shopifyProduct, RexData $rexData, Collection $mappings)
    {
        $variant = $this->getVariant($rexProduct, $shopifyProduct, $rexData, $mappings);
        $variant['title'] = 'Default Title';
        $variants[] = $variant;
        return $variants;
    }

    protected function getVariant(RexProduct $rexProduct, ShopifyProduct $shopifyProduct, RexData $rexData, Collection $mappings)
    {
        $inventory_policy_value = $this->getInventoryPolicy($rexData);
     
        $variantData = [
            'sku'                  => $this->getSku($rexData),
            'taxable'              => $this->getTaxable($rexData),
            'weight'               => $this->getWeight($rexData),
            'inventory_management' => $this->getInventoryManagement(),
            'fulfillment_service'  => $this->getFulfillmentService($shopifyProduct, $rexData),
            'inventory_policy'     => $inventory_policy_value,
            'requires_shipping'    => $this->getRequiresShipping($rexData)
        ];

        $mappedValue = $this->getMappedFieldValue($rexData, $mappings, 'price');
        if ( $mappedValue <> 'off' ) {
            $price = $this->getPrice($rexData, $mappings);
            $variantData['price']=$price;
        }
        
        $mappedValue = $this->getMappedFieldValue($rexData, $mappings, 'compare_at_price');
        if ( $mappedValue <> 'off' ) {
            $compareAtPrice = $this->getCompareAtPrice($rexData, $mappings);
            $variantData['compare_at_price']=$compareAtPrice;
        }

        $sizeOption = $this->getSizeOption($rexData);
        $colourOption = $this->getColourOption($rexData);

        if (isset($sizeOption) && isset($colourOption)) {
            $variantData['option1'] = $sizeOption;
            $variantData['option2'] = $colourOption;
        } elseif (isset($sizeOption)) {
            $variantData['option1'] = $sizeOption;
        } elseif (isset($colourOption)) {
            $variantData['option1'] = $colourOption;
        } else {
            $variantData['option1'] = 'Default Title';
        }

        $rexProduct->has_size = isset($sizeOption);
        $rexProduct->has_colour = isset($colourOption);
        $rexProduct->save();

        $existingVariant = $this->shopifyProductRepository->getVariant($rexProduct->id, $shopifyProduct->id);
        if (isset($existingVariant->external_id)) {
            $variantData['id'] = $existingVariant->external_id;
        }

        return $variantData;
    }

    protected function getPrice(RexData $rexData, Collection $mappings)
    {
        return $rexData->getPricingStructure()->getDefaultPrice()->toNative();
    }

    protected function getCompareAtPrice(RexData $rexData, Collection $mappings)
    {
        $pricingStructure = $rexData->getPricingStructure();
        if ($pricingStructure->hasRrp()
            && $pricingStructure->getRrp()->toNative() > $pricingStructure->getDefaultPrice()->toNative()
        ) {
            return $pricingStructure->getRrp()->toNative();
        } else {
            return null;
        }
    }

    protected function getSku(RexData $rexData)
    {
        return $rexData->getSku()->toNative();
    }

    protected function getTaxable(RexData $rexData)
    {
        return $rexData->isVoucherProduct() ? false : $rexData->getPricingStructure()->isTaxable();
    }

    protected function getWeight(RexData $rexData)
    {
        return $rexData->getPhysicalPackage()->getWeight()->toNative();
    }

    protected function getSizeOption(RexData $rexData)
    {
        return $rexData->isVoucherProduct() ? null : $this->getAttributeValue($rexData, 'size');
    }

    protected function getColourOption(RexData $rexData)
    {
        return $rexData->isVoucherProduct() ? null : $this->getAttributeValue($rexData, 'colour');
    }

    protected function getOptions(RexData $rexData)
    {
        $options = array();
        $sizeOption = $this->getSizeOption($rexData);
        $colourOption = $this->getColourOption($rexData);
        if (isset($sizeOption)) {
            $options[] = [
                'name' => 'Size',
                'values' => [$sizeOption]
            ];
        }
        if (isset($colourOption)) {
            $options[] = [
                'name' => 'Colour',
                'values' => [$colourOption]
            ];
        }
        if (!isset($sizeOption) && !isset($colourOption)) {
            $options[] = [
                'name' => 'Title',
                'values' => ['Default Title']
            ];
        }
        return $options;
    }

    protected function getVersion(RexData $rexData)
    {
        return $rexData->getLastUpdated();
    }

    // This does not work on update as the IDs need to be passed through
    protected function getMetafields(RexData $rexData)
    {
        return [
            $this->getRexIdMetafield($rexData)
        ];
    }

    protected function getRexIdMetafield(RexData $rexData)
    {
        return [
            'key' => 'rex_id',
            'value' => $rexData->getId()->toNative(),
            'value_type' => 'integer',
            'namespace' => 'rex'
        ];
    }

    protected function getTags(RexData $rexData)
    {
        return 'Simple, ' . $rexData->getId()->toInteger();
    }

    protected function getTagsFromGroup(RexGroupData $rexData)
    {
        $tags = ['Matrix', $rexData->getManufacturerSku()->toNative()];
        foreach ($rexData->getProducts() as $product) {
            $tags[] = $product->getId()->toInteger();
        }
        return implode(', ', $tags);
    }

    protected function getInventoryPolicy(RexData $rexData)
    {

        // Find shopify store with preorders field
        $preOrder = ShopifyStore::where('id',$this->shopify_store_id)->get()->pluck('preorders');

        // Make sure sales channel id has been set
        if (empty($this->rex_sales_channel_id))
        {
            $rex_sales_channel = ShopifyStore::where('id',$this->shopify_store_id)->get()->pluck('rex_sales_channel_id');
            $this->rex_sales_channel_id = $rex_sales_channel[0];   
        }
        if (count($preOrder) === 0){
            throw new \ModelNotFoundException('Shopify store not found.');
        }
        $preOrder = $preOrder[0];
        $defaultPolicy = $rexData->getInventoryItem()->isManaged() ? 'deny' : 'continue';
        // check the "preorders" field for the associated Shopify store 
        // where shopify_store preorders = 0 
        if ($preOrder === 1 || $preOrder > 3)
        {
            return $defaultPolicy;
        }
        // check that the product is enabled for pre-order
        elseif ($preOrder === 2)
        {
          if( $rexData->isPreorderProduct() === 0)
            {
                return $defaultPolicy;
            }
            /*
                we need the Available and StockForPreOrder quantity 
                Retrive inventory buffer weather store default or custom
                If Available <= inventory_buffer (or 0 if no buffer) AND StockForPreOrder > 0, return"continue"
                Else, return "deny"
            */
            $available_qty = $rexData->getInventoryItem()->getQtyAvailable()->toNative();
            $stock_for_pre_order_qty = $rexData->getInventoryItem()->getStockForPreOrder()->toNative(); 

            $inventory_buffer = $this->getInventoryBufferQty($rexData->getProductType()->getId()->toNative(),$this->rex_sales_channel_id);


            if($available_qty <= $inventory_buffer && $stock_for_pre_order_qty > 0){
                return 'continue';
            }else{
                return 'deny';
            }
        } 
        elseif ($preOrder === 3)
        {
          if( $rexData->isPreorderProduct() === 0)
            {
                return $defaultPolicy;
            }
            // we only need Available and the inventory buffer
            $available_qty = $rexData->getInventoryItem()->getQtyAvailable()->toNative();

            $inventory_buffer = $this->getInventoryBufferQty($rexData->getProductType()->getId()->toNative(),$this->rex_sales_channel_id);

            if($available_qty <= $inventory_buffer){
                return 'continue';
            }else{
                return 'deny';
            }
        }
    }


    protected function getInventoryManagement()
    {
        return 'shopify';
    }

    protected function getRequiresShipping(RexData $rexData)
    {
        return !$rexData->isVoucherProduct();
    }

    protected function getFulfillmentService(ShopifyProduct $shopifyProduct, RexData $rexData)
    {
        $fulfillmentService = $shopifyProduct->shopifyStore->shopifyFulfillmentService;
        if ($rexData->isVoucherProduct()) {
            return 'manual';
        } elseif(isset($fulfillmentService) && $fulfillmentService->hasBeenSynced()) {
            return $fulfillmentService->handle;
        } else {
            throw new \Exception('Cannot map to Shopify product ' . $shopifyProduct->id
                . ' as no fulfillment service has been synced.');
        }
    }
}
