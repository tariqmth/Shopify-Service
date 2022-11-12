<?php

namespace App\Models\Order;

use App\Models\Product\ShopifyProduct;

class ShopifyOrderRepository
{
    public function get($shopifyStoreId, $externalId)
    {
        return ShopifyOrder
            ::where('external_id', $externalId)
            ->where('shopify_store_id', $shopifyStoreId)
            ->first();
    }

    public function getOrCreate($shopifyStoreId, $externalId)
    {
        $shopifyOrder = $this->get($shopifyStoreId, $externalId);

        if (!isset($shopifyOrder)) {
            $shopifyOrder = new ShopifyOrder;
            $shopifyOrder->shopify_store_id = $shopifyStoreId;
            $shopifyOrder->external_id = $externalId;
            $shopifyOrder->save();
        }

        return $shopifyOrder;
    }

    public function getItem(ShopifyOrder $shopifyOrder, $externalId)
    {
        return ShopifyOrderItem
            ::where('shopify_order_id', $shopifyOrder->id)
            ->where('external_id', $externalId)
            ->first();
    }

    public function createItem(ShopifyOrder $shopifyOrder, $externalId)
    {
        $shopifyOrderItem = new ShopifyOrderItem;
        $shopifyOrderItem->shopifyOrder()->associate($shopifyOrder);
        $shopifyOrderItem->external_id = $externalId;
        $shopifyOrderItem->save();
        return $shopifyOrderItem;
    }
}