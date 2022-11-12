<?php


namespace App\Models\Fulfillment;


use App\Models\Order\RexOrder;

class RexFulfillmentRepository
{
    public function createBatch(RexOrder $rexOrder, $externalIdsHash)
    {
        $rexFulfillmentBatch = new RexFulfillmentBatch;
        $rexFulfillmentBatch->rexOrder()->associate($rexOrder);
        $rexFulfillmentBatch->external_ids_hash = $externalIdsHash;
        $rexFulfillmentBatch->save();
        return $rexFulfillmentBatch;
    }

    public function getOrCreateBatch(RexOrder $rexOrder, $externalIdsHash)
    {
        $rexFulfillmentBatch = RexFulfillmentBatch
            ::where('rex_order_id', $rexOrder->id)
            ->where('external_ids_hash', $externalIdsHash)
            ->first();

        if (!isset($rexFulfillmentBatch)) {
            $rexFulfillmentBatch = $this->createBatch($rexOrder, $externalIdsHash);
        }

        return $rexFulfillmentBatch;
    }

    public function create(RexOrder $rexOrder, RexFulfillmentBatch $rexFulfillmentBatch, $externalId)
    {
        $rexFulfillment = new RexFulfillment;
        $rexFulfillment->rexFulfillmentBatch()->associate($rexFulfillmentBatch);
        $rexFulfillment->rexOrder()->associate($rexOrder);
        $rexFulfillment->external_id = $externalId;
        $rexFulfillment->save();
        return $rexFulfillment;
    }

    public function getOrCreate(RexOrder $rexOrder, RexFulfillmentBatch $rexFulfillmentBatch, $externalId)
    {
        $rexFulfillment = RexFulfillment
            ::where(function ($query) use ($rexOrder, $rexFulfillmentBatch) {
                $query->where('rex_fulfillment_batch_id', $rexFulfillmentBatch->id)
                      ->orWhere('rex_order_id', $rexOrder->id);
            })
            ->where('external_id', $externalId)
            ->first();

        if (!isset($rexFulfillment)) {
            $rexFulfillment = $this->create($rexOrder, $rexFulfillmentBatch, $externalId);
        }

        return $rexFulfillment;
    }
}
