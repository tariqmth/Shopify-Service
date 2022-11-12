<?php

namespace App\Models\Fulfillment;

use App\Models\Syncable;

class RexFulfillmentBatch extends Syncable
{
    public function rexOrder()
	{
		return $this->belongsTo('App\Models\Order\RexOrder');
	}

	public function rexFulfillments()
	{
		return $this->hasMany('App\Models\Fulfillment\RexFulfillment');
	}

	public function shopifyFulfillment()
	{
		return $this->hasOne('App\Models\Fulfillment\ShopifyFulfillment');
	}
}