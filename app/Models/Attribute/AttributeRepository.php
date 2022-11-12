<?php

namespace App\Models\Attribute;

use App\Models\Syncer\SyncerRepository;
use App\Packages\SkylinkSdkFactory;
use RetailExpress\SkyLink\Sdk\Catalogue\Attributes\AttributeCode;
use RetailExpress\SkyLink\Sdk\Catalogue\Attributes\AttributeOption as AttributeOptionData;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\SimpleProduct as RexProductData;

class AttributeRepository
{
    private $skylinkSdkFactory;
    private $syncerRepository;

    public function __construct(
        SkylinkSdkFactory $skylinkSdkFactory,
        SyncerRepository $syncerRepository
    ) {
        $this->skylinkSdkFactory = $skylinkSdkFactory;
        $this->syncerRepository = $syncerRepository;
    }

    public function createAttributes($clientId, $attributesData)
    {
        foreach ($attributesData as $attributeData) {
            $this->createAttribute($clientId, $attributeData->getCode()->getValue());
            foreach ($attributeData->getOptions() as $optionData) {
                $this->createAttributeOptionFromData($clientId, $optionData);
            }
        }
    }

    public function createAttribute($clientId, $attributeName)
    {
        return RexAttribute::firstOrCreate([
            'client_id' => $clientId,
            'name' => $attributeName
        ]);
    }

    public function createAttributeOption($attributeId, $optionId, $optionValue = null)
    {
        $option = RexAttributeOption::firstOrCreate([
            'rex_attribute_id' => $attributeId,
            'option_id' => $optionId
        ]);
        if (isset($optionValue) && $option->value !== $optionValue) {
            $option->value = $optionValue;
            $option->save();
        }
        return $option;
    }

    public function createAttributeOptionFromData($clientId, AttributeOptionData $attributeOptionData)
    {
        $attributeName = $attributeOptionData->getAttribute()->getCode()->getValue();
        $attribute = $this->createAttribute($clientId, $attributeName);
        $optionValue = $attributeOptionData->getLabel() ? $attributeOptionData->getLabel()->toNative() : null;
        $option = $this->createAttributeOption(
            $attribute->id,
            $attributeOptionData->getId()->toNative(),
            $optionValue
        );
        return $option;
    }

    public function createAttributeOptionsFromProductData($clientId, RexProductData $rexProductData)
    {
        $optionsData = $rexProductData->getAttributeOptions();
        foreach ($optionsData as $optionData) {
            if ($optionData->getAttribute()->getCode()->isPredefined() && $optionData->getLabel()) {
                $this->createAttributeOptionFromData($clientId, $optionData);
            }
        }
    }
}
