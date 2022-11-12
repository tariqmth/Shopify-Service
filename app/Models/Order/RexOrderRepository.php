<?php

namespace App\Models\Order;

use App\Models\Customer\RexCustomer;
use App\Models\Product\RexProduct;

class RexOrderRepository
{
    public function getOrCreate(ShopifyOrder $shopifyOrder, RexCustomer $rexCustomer)
    {
        if ($shopifyOrder->shopifyStore->client_id !== $rexCustomer->rexSalesChannel->client_id) {
            throw new \Exception('Cannot get Rex order. Shopify order and Rex customer do not share client.');
        }

        if (isset($shopifyOrder->rexOrder)) {
            return $shopifyOrder->rexOrder;
        }

        $rexSalesChannel = $shopifyOrder->shopifyStore->rexSalesChannel;

        $rexOrder = new RexOrder;
        $rexOrder->rexSalesChannel()->associate($rexSalesChannel);
        $rexOrder->rexCustomer()->associate($rexCustomer);
        $rexOrder->save();

        $shopifyOrder->rexOrder()->associate($rexOrder);
        $shopifyOrder->save();

        return $rexOrder;
    }

    public function get($rexSalesChannelId, $rexOrderExternalId)
    {
        if (!isset($rexSalesChannelId) || !isset($rexOrderExternalId)) {
            throw new \Exception('Sales channel ID and external ID cannot be null when getting Rex order.');
        }

        return RexOrder
            ::where('rex_sales_channel_id', $rexSalesChannelId)
            ->where('external_id', $rexOrderExternalId)
            ->first();
    }

    public function createItem(RexOrderProduct $rexOrderProduct, $externalId)
    {
        $rexOrderItem = new RexOrderItem;
        $rexOrderItem->rexOrderProduct()->associate($rexOrderProduct);
        $rexOrderItem->external_id = $externalId;
        $rexOrderItem->save();

        return $rexOrderItem;
    }

    public function getItem($rexOrderProductId, $externalId)
    {
        return RexOrderItem
            ::where('rex_order_product_id', $rexOrderProductId)
            ->where('external_id', $externalId)
            ->first();
    }

    public function getItemByOrder($rexOrderId, $externalId)
    {
        $rexOrder = RexOrder::findOrFail($rexOrderId);
        $rexOrderProductIds = $rexOrder->rexOrderProducts->pluck('id');
        return RexOrderItem
            ::where('external_id', $externalId)
            ->whereIn('rex_order_product_id', $rexOrderProductIds)
            ->first();
    }

    public function createOrderProduct(RexOrder $rexOrder, RexProduct $rexProduct, $price = null)
    {
        $rexOrderProduct = new RexOrderProduct;
        $rexOrderProduct->rexOrder()->associate($rexOrder);
        $rexOrderProduct->rexProduct()->associate($rexProduct);
        $rexOrderProduct->price = $price;
        $rexOrderProduct->save();
        return $rexOrderProduct;
    }

    public function getOrderProduct($rexOrderId, $rexProductId, $price = null)
    {
        return RexOrderProduct
            ::where('rex_order_id', $rexOrderId)
            ->where('rex_product_id', $rexProductId)
            ->where(function($query) use ($price) {
                $query->where('price', $price)
                    ->orWhereNull('price');
            })->first();
    }

    public function getOrderProductById($rexOrderId, $rexOrderProductId)
    {
        return RexOrderProduct
            ::where('rex_order_id', $rexOrderId)
            ->where('id', $rexOrderProductId)
            ->first();
    }
}