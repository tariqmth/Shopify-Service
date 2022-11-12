<?php

namespace App\Models\Mapper;

use App\Models\Attribute\AttributeRepository;
use App\Models\Product\RexProduct;
use App\Models\Product\RexProductGroup;
use App\Models\Product\ShopifyProduct;
use RetailExpress\SkyLink\Sdk\Catalogue\Attributes\AttributeCode;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\SimpleProduct as RexData;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\Matrix as RexGroupData;

class ActiveShopifyProductMapper extends Mapper
{
    public function getMappedData(ShopifyProduct $shopifyProduct)
    {
        $slaveData = [
            'published_at' => $this->getPublishedAt()
            /*
             * Tags have been temporarily disabled until a solution is developed that will allow
             * Shopify store owners to keep their existing tags without being overwritten.
             *
             * 'tags' => $this->getTags($shopifyProduct)
             */
        ];

        return $slaveData;
    }

    protected function getPublishedAt()
    {
        $now = new \DateTime();
        return $now->format(\DateTime::ATOM);
    }

    protected function getTags(ShopifyProduct $shopifyProduct)
    {
        $tags = [];
        $rexProduct = $shopifyProduct->rexProduct;
        $rexProductGroup = $shopifyProduct->rexProductGroup;

        if (isset($rexProduct)) {
            $tags[] = 'Simple';
            $tags[] = $rexProduct->external_id;
        } elseif (isset($rexProductGroup)) {
            $tags[] = 'Matrix';
            foreach ($rexProductGroup->rexProducts as $productOfGroup) {
                $tags[] = $productOfGroup->external_id;
            }
        }

        return implode(', ', $tags);
    }
}
