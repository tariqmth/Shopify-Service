<?php

namespace App\Models\Mapper;

use App\Models\Fulfillment\ShopifyFulfillment;
use App\Models\Location\ShopifyLocation;

class ShopifyVoucherFulfillmentMapper
{
    public function getMappedData(
        ShopifyFulfillment $shopifyFulfillment,
        $shopifyLineItemData
    ) {
        return [
            'location_id' => $this->getLocationId($shopifyFulfillment),
            'tracking_number' => null,
            'line_items' => $this->getLineItems($shopifyLineItemData),
            'notify_customer' => false
        ];
    }

    protected function getLocationId(ShopifyFulfillment $shopifyFulfillment)
    {
        $shopifyStore = $shopifyFulfillment->shopifyOrder->shopifyStore;
        $location = ShopifyLocation::where('shopify_store_id', $shopifyStore->id)->where('is_primary', true)->first();
        if (isset($location->external_id)) {
            return $location->external_id;
        } else {
            throw new \Exception('No primary location could be found to fulfill voucher product.');
        }
    }

    protected function getLineItems($shopifyLineItemData) {
        return [
            [
                'id' => $shopifyLineItemData->id,
                'quantity' => $shopifyLineItemData->quantity
            ]
        ];
    }
}
