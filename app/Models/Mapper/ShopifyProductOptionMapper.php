<?php

namespace App\Models\Mapper;

use App\Models\Attribute\AttributeRepository;
use App\Models\Product\RexProduct;
use App\Models\Product\RexProductGroup;
use App\Models\Product\ShopifyProduct;
use App\Models\Product\ShopifyProductRepository;
use RetailExpress\SkyLink\Sdk\Catalogue\Attributes\AttributeOption as RexOptionData;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\ProductId;

/*
 * Deprecated as variant ordering is too complex. Rex products are simply resynced instead.
 */
class ShopifyProductOptionMapper extends Mapper
{
    protected $attributeRepository;
    protected $shopifyProductRepository;
    protected $clientId;

    public function __construct(
        AttributeRepository $attributeRepository,
        ShopifyProductRepository $shopifyProductRepository
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->shopifyProductRepository = $shopifyProductRepository;
    }

    public function getMappedData(RexProduct $rexProduct, ShopifyProduct $shopifyProduct, RexOptionData $rexOptionData)
    {
        $this->clientId = $rexProduct->rexSalesChannel->client->id;
        $attributeCode = $rexOptionData->getAttribute()->getCode()->getValue();
        $slaveData = $this->getMappedDataForBaseOptions($rexOptionData);

        if ($attributeCode === 'colour' || $attributeCode === 'size') {
            $slaveData['variants'] = $this->getVariants($rexProduct, $shopifyProduct, $rexOptionData);
        }

        return $slaveData;
    }

    public function getMappedDataFromGroup(RexProductGroup $rexProductGroup, ShopifyProduct $shopifyProduct, RexOptionData $rexOptionData)
    {
        $this->clientId = $rexProductGroup->rexSalesChannel->client->id;
        $attributeCode = $rexOptionData->getAttribute()->getCode()->getValue();
        $slaveData = $this->getMappedDataForBaseOptions($rexOptionData);

        if ($attributeCode === 'colour' || $attributeCode === 'size') {
            $slaveData['variants'] = $this->getVariantsFromGroup($rexProductGroup, $shopifyProduct, $rexOptionData);
        }

        return $slaveData;
    }

    protected function getMappedDataForBaseOptions(RexOptionData $rexOptionData)
    {
        $slaveData = array();
        $attributeCode = $rexOptionData->getAttribute()->getCode()->getValue();

        if ($attributeCode === 'product_type') {
            $slaveData['product_type'] = $this->getProductType($rexOptionData);
        } elseif ($attributeCode === 'brand') {
            $slaveData['vendor'] = $this->getVendor($rexOptionData);
        }

        return $slaveData;
    }

    protected function getProductType(RexOptionData $rexOptionData)
    {
        return $this->getAttributeValue($rexOptionData, 'product_type');
    }

    protected function getVendor(RexOptionData $rexOptionData)
    {
        return $this->getAttributeValue($rexOptionData, 'brand');
    }

    protected function getSizeOption(RexOptionData $rexOptionData)
    {
        return $this->getAttributeValue($rexOptionData, 'size');
    }

    protected function getColourOption(RexOptionData $rexOptionData)
    {
        return $this->getAttributeValue($rexOptionData, 'colour');
    }

    protected function getAttributeValue(RexOptionData $rexOptionData)
    {
        if (!isset($rexOptionData) || !$rexOptionData->getLabel()) {
            return null;
        }
        $this->attributeRepository->createAttributeOptionFromData($this->clientId, $rexOptionData);
        $value = $rexOptionData->getLabel()->toNative();
        return $value;
    }

    protected function getVariants(RexProduct $rexProduct, ShopifyProduct $shopifyProduct, RexOptionData $rexOptionData)
    {
        $variant = $this->getVariant($rexProduct, $shopifyProduct, $rexOptionData);
        $variants[] = $variant;
        return $variants;
    }

    protected function getVariantsFromGroup(RexProductGroup $rexProductGroup, ShopifyProduct $shopifyProduct, RexOptionData $rexOptionData)
    {
        $variants = array();
        foreach ($rexProductGroup->rexProducts as $rexProduct) {
            $variants[] = $this->getVariant($rexProduct, $shopifyProduct, $rexOptionData);
        }
        return $variants;
    }

    protected function getVariant(RexProduct $rexProduct, ShopifyProduct $shopifyProduct, RexOptionData $rexOptionData)
    {
        $variantData = array();

        $skylinkProductId = ProductId::fromNative($rexProduct->external_id);

        if (in_array($skylinkProductId, $rexOptionData->getProductIds())) {
            $attributeCode = $rexOptionData->getAttribute()->getCode()->getValue();
            if ($attributeCode === 'size') {
                $variantData['option1'] = $this->getSizeOption($rexOptionData);
            } elseif ($attributeCode === 'colour' && $rexProduct->has_size) {
                $variantData['option2'] = $this->getColourOption($rexOptionData);
            } elseif ($attributeCode === 'colour') {
                $variantData['option1'] = $this->getColourOption($rexOptionData);
            }
        }

        $existingVariant = $this->shopifyProductRepository->getVariant($rexProduct->id, $shopifyProduct->id);
        if (isset($existingVariant->external_id)) {
            $variantData['id'] = $existingVariant->external_id;
        }

        return $variantData;
    }
}
