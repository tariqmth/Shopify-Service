<?php

namespace App\Models\Mapper;

use App\Models\Fulfillment\ShopifyFulfillment;
use App\Models\Order\RexOrder;
use App\Models\Order\RexOrderItem;
use App\Models\Order\RexOrderRepository;
use RetailExpress\SkyLink\Sdk\Sales\Fulfillments\Batch as RexFulfillmentBatchData;
use RetailExpress\SkyLink\Sdk\Sales\Fulfillments\Fulfillment as RexFulfillmentData;

class ShopifyFulfillmentMapper
{
    protected $rexOrderRepository;

    public function __construct(
        RexOrderRepository $rexOrderRepository
    ) {
        $this->rexOrderRepository = $rexOrderRepository;
    }

    public function getMappedData(
        ShopifyFulfillment $shopifyFulfillment,
        RexFulfillmentBatchData $rexFulfillmentBatchData
    ) {
        $rexOrder = $shopifyFulfillment->rexFulfillmentBatch->rexOrder;

        return [
            'location_id' => $this->getLocationId($shopifyFulfillment),
            'tracking_number' => null,
            'line_items' => $this->getLineItems($rexOrder, $rexFulfillmentBatchData)
        ];
    }

    protected function getLocationId(ShopifyFulfillment $shopifyFulfillment)
    {
        return $shopifyFulfillment->shopifyOrder->shopifyStore->shopifyFulfillmentService->shopifyLocation->external_id;
    }

    protected function getLineItems(
        RexOrder $rexOrder,
        RexFulfillmentBatchData $rexFulfillmentBatchData
    ) {
        $lineItems = array();
        foreach ($rexFulfillmentBatchData->getFulfillments() as $rexFulfillmentData) {
            $rexOrderItem = $this->rexOrderRepository->getItemByOrder(
                $rexOrder->id,
                $rexFulfillmentData->getOrderItemId()->toNative()
            );
            $shopifyOrderItem = $rexOrderItem->rexOrderProduct->shopifyOrderItem;
            if (!isset($shopifyOrderItem)) {
                throw new \Exception('Rex order item ' . $rexOrderItem->id
                    . ' cannot be matched to a Shopify order item.');
            }
            $quantity = $rexFulfillmentData->getQty()->toNative();
            $lineItems = $this->addToLineItems($lineItems, $shopifyOrderItem->external_id, $quantity);
        }
        return $lineItems;
    }

    protected function addToLineItems(array $lineItems, $shopifyLineItemExternalId, $quantity)
    {
        foreach ($lineItems as $index => $lineItem) {
            if ($lineItem['id'] === $shopifyLineItemExternalId) {
                $lineItems[$index]['quantity'] += $quantity;
                return $lineItems;
            }
        }
        $lineItems[] = [
            'id' => $shopifyLineItemExternalId,
            'quantity' => $quantity
        ];
        return $lineItems;
    }
}
