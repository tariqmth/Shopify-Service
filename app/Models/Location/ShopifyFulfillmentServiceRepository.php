<?php

namespace App\Models\Location;

use App\Models\Store\ShopifyStore;

class ShopifyFulfillmentServiceRepository
{
    public function createFulfillmentService(ShopifyStore $shopifyStore)
    {
        $fulfillmentService = $shopifyStore->shopifyFulfillmentService;
        if (!isset($fulfillmentService)) {
            $fulfillmentService = new ShopifyFulfillmentService;
            $fulfillmentService->shopifyStore()->associate($shopifyStore);
            $fulfillmentService->save();
        }
        return $fulfillmentService;
    }

    public function createLocation(ShopifyFulfillmentService $shopifyFulfillmentService)
    {
        $location = $shopifyFulfillmentService->shopifyLocation;
        if (!isset($location)) {
            $location = new ShopifyLocation;
            $location->shopifyFulfillmentService()->associate($shopifyFulfillmentService);
            $location->shopifyStore()->associate($shopifyFulfillmentService->shopifyStore);
            $location->save();
        }
        return $location;
    }
}