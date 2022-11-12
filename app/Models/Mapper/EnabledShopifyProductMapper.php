<?php

namespace App\Models\Mapper;

use App\Models\Attribute\AttributeRepository;
use App\Models\Product\RexProduct;
use App\Models\Product\RexProductGroup;
use App\Models\Product\ShopifyProduct;
use RetailExpress\SkyLink\Sdk\Catalogue\Attributes\AttributeCode;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\SimpleProduct as RexData;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\Matrix as RexGroupData;

class EnabledShopifyProductMapper extends Mapper
{
    public function getMappedData()
    {
        $slaveData = [
            'published_at' => $this->getPublishedAt()
        ];

        return $slaveData;
    }

    protected function getPublishedAt()
    {
        $now = new \DateTime();
        return $now->format(\DateTime::ATOM);
    }
}
