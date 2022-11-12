<?php

namespace App\Models\Order;

class ShopifyOrderPriceCalculator
{
    public function getDiscountedItemPrice($shopifyOrderItemData)
    {
        if (isset($shopifyOrderItemData->discount_allocations)) {
            $discount = 0;
            foreach ($shopifyOrderItemData->discount_allocations as $discountAllocation) {
                $discount += $discountAllocation->amount;
            }
            $discountPerItem = $discount / $shopifyOrderItemData->quantity;
            $discountPerItem = round($discountPerItem, 2);
            $price = $shopifyOrderItemData->price - $discountPerItem;
        } else {
            $price = $shopifyOrderItemData->price;
        }
        return $price;
    }
}