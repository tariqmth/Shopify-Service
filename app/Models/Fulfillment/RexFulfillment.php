<?php

namespace App\Models\Fulfillment;

use App\Models\Syncable;

class RexFulfillment extends Syncable
{
    public function rexFulfillmentBatch()
	{
		return $this->belongsTo('App\Models\Fulfillment\RexFulfillmentBatch');
	}

	public function rexOrder()
	{
		return $this->belongsTo('App\Models\Order\RexOrder');
	}
}