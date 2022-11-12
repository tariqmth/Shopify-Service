<?php

namespace App\Models\Mapper;

use App\Models\Attribute\AttributeRepository;
use App\Models\Attribute\RexAttribute;
use App\Models\Attribute\RexAttributeOption;
use App\Models\Product\RexProduct;
use App\Models\Product\RexProductGroup;
use App\Models\Product\ShopifyProduct;
use App\Models\Product\ShopifyProductRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use RetailExpress\SkyLink\Sdk\Catalogue\Attributes\AttributeCode;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\SimpleProduct as RexData;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\Matrix as RexGroupData;

class ShopifyInventoryItemMapper extends Mapper
{
    public function getMappedData(RexData $rexData)
    {
        $slaveData = [
            'sku'     => $this->getSku($rexData),
            'tracked' => true
        ];

        return $slaveData;
    }

    protected function getSku(RexData $rexData)
    {
        return $rexData->getSku()->toNative();
    }
}
