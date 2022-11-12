<?php

namespace App\Models\Fulfillment;

use RetailExpress\SkyLink\Sdk\Sales\Fulfillments\Batch as RexFulfillmentBatchData;

class ShopifyFulfillmentRepository
{
    public function create(RexFulfillmentBatch $rexFulfillmentBatch)
    {
        $shopifyFulfillment = new ShopifyFulfillment;
        $shopifyFulfillment->rexFulfillmentBatch()->associate($rexFulfillmentBatch);

        $shopifyOrder = $rexFulfillmentBatch->rexOrder->shopifyOrder;
        if (isset($shopifyOrder)) {
            $shopifyFulfillment->shopifyOrder()->associate($shopifyOrder);
        } else {
            throw new \Exception('Cannot create Shopify fulfillment for Rex fulfillment '
                . 'where Rex order is not associated with a Shopify order.');
        }

        $shopifyFulfillment->save();

        return $shopifyFulfillment;
    }

    public function getOrCreate(RexFulfillmentBatch $rexFulfillmentBatch, RexFulfillmentBatchData $rexFulfillmentBatchData = null)
    {
        if (isset($rexFulfillmentBatch->shopifyFulfillment)) {
            return $rexFulfillmentBatch->shopifyFulfillment;
        }

        // Match existing fulfillment from external source (if necessary) to Rex fulfillment batch
        if ($rexFulfillmentBatchData
            && count($rexFulfillmentBatchData->getFulfillments()) > 0
            && isset($rexFulfillmentBatch->rexOrder->shopifyOrder)
        ) {
            $existingFulfillments = $rexFulfillmentBatch->rexOrder->shopifyOrder->shopifyFulfillments;
            foreach ($existingFulfillments as $existingFulfillment) {
                if ($existingFulfillment->isFromForeignSource()
                    && $this->shopifyFulfillmentMatchesRex($existingFulfillment, $rexFulfillmentBatchData)
                ) {
                    $existingFulfillment->rexFulfillmentBatch()->associate($rexFulfillmentBatch);
                    $existingFulfillment->save();
                    return $existingFulfillment;
                }
            }
        }

        return $this->create($rexFulfillmentBatch);
    }

    public function getOrCreateByExternalId($shopifyOrderId, $externalId)
    {
        $shopifyFulfillment = ShopifyFulfillment
            ::where('shopify_order_id', $shopifyOrderId)
            ->where('external_id', $externalId)->first();

        if (!isset($shopifyFulfillment)) {
            $shopifyFulfillment = new ShopifyFulfillment;
            $shopifyFulfillment->shopify_order_id = $shopifyOrderId;
            $shopifyFulfillment->external_id = $externalId;
            $shopifyFulfillment->save();
        }

        return $shopifyFulfillment;
    }

    public function getOrCreateItem($shopifyFulfillmentId, $shopifyOrderItemId, $quantity)
    {
        $item = ShopifyFulfillmentItem
            ::where('shopify_fulfillment_id', $shopifyFulfillmentId)
            ->where('shopify_order_item_id', $shopifyOrderItemId)
            ->first();

        if (!isset($item)) {
            $item = new ShopifyFulfillmentItem;
            $item->shopify_fulfillment_id = $shopifyFulfillmentId;
            $item->shopify_order_item_id = $shopifyOrderItemId;
            $item->quantity = $quantity;
            $item->save();
        }

        return $item;
    }

    private function shopifyFulfillmentMatchesRex(ShopifyFulfillment $shopifyFulfillment, RexFulfillmentBatchData $rexFulfillmentBatchData)
    {
        $shopifyItemQuantities = [];
        $rexItemQuantities = [];

        foreach ($shopifyFulfillment->shopifyFulfillmentItems as $shopifyFulfillmentItem) {
            if (!isset($shopifyFulfillmentItem->shopifyOrderItem->rexOrderProduct->rexOrderItems)) {
                continue;
            }
            $rexOrderItems = $shopifyFulfillmentItem->shopifyOrderItem->rexOrderProduct->rexOrderItems;
            foreach ($rexOrderItems as $rexOrderItem) {
                $externalItemId = $rexOrderItem->external_id;
                if (!isset($externalItemId)) {
                    continue;
                }
                if (isset($shopifyItemQuantities[$externalItemId])) {
                    $shopifyItemQuantities[$externalItemId] += $shopifyFulfillmentItem->quantity;
                } else {
                    $shopifyItemQuantities[$externalItemId] = $shopifyFulfillmentItem->quantity;
                }
            }
        }

        foreach ($rexFulfillmentBatchData->getFulfillments() as $rexFulfillmentData) {
            $externalItemId = $rexFulfillmentData->getOrderItemId()->toNative();
            if (isset($rexItemQuantities[$externalItemId])) {
                $rexItemQuantities[$externalItemId] += $rexFulfillmentData->getQty()->toNative();
            } else {
                $rexItemQuantities[$externalItemId] = $rexFulfillmentData->getQty()->toNative();
            }
        }

        return $shopifyItemQuantities == $rexItemQuantities;
    }
}