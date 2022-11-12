<?php

namespace App\Models\Mapper;

use App\Models\Attribute\AttributeRepository;
use App\Models\Attribute\RexAttribute;
use App\Models\Attribute\RexAttributeOption;
use App\Models\Inventory\ShopifyInventoryItem;
use App\Models\Location\ShopifyLocation;
use App\Models\Product\RexProduct;
use App\Models\Product\RexProductGroup;
use App\Models\Product\ShopifyProduct;
use App\Models\Product\ShopifyProductRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use RetailExpress\SkyLink\Sdk\Catalogue\Attributes\AttributeCode;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\SimpleProduct as RexData;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\Matrix as RexGroupData;

class ShopifyInventoryLevelMapper extends Mapper
{
    public function getMappedData(
        ShopifyInventoryItem $shopifyInventoryItem,
        ShopifyLocation $shopifyLocation,
        RexData $rexData,
        $updatedOutletQtyData
    ) {
        $slaveData = [
            'location_id'             => $this->getLocationId($shopifyLocation),
            'inventory_item_id'       => $this->getInventoryItemId($shopifyInventoryItem),
            'available'               => $this->getAvailable($rexData,$updatedOutletQtyData),
            'disconnect_if_necessary' => true
        ];
        return $slaveData;
    }

    protected function getLocationId(ShopifyLocation $shopifyLocation)
    {
        if (isset($shopifyLocation->external_id)) {
            return $shopifyLocation->external_id;
        } else {
            throw new \Exception("Can't map Shopify inventory level for unsynced location "
                . $shopifyLocation->id);
        }
    }

    protected function getInventoryItemId(ShopifyInventoryItem $shopifyInventoryItem)
    {
        if (isset($shopifyInventoryItem->external_id)) {
            return $shopifyInventoryItem->external_id;
        } else {
            throw new \Exception("Can't map Shopify inventory level for unsynced inventory item "
                . $shopifyInventoryItem->id);
        }
    }

    protected function getAvailable($rexData,$updatedOutletQtyData)
    {
        $qty_available = $rexData->getInventoryItem()->getQtyAvailable()->toNative();
        $total_qty_available = $qty_available - $updatedOutletQtyData;

        return $total_qty_available ;
    }
}
