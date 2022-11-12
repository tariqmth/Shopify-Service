<?php

namespace App\Models\Fulfillment;

use App\Models\Syncable;

class ShopifyFulfillmentItem extends Syncable
{
    public function shopifyFulfillment()
	{
		return $this->belongsTo('App\Models\Fulfillment\ShopifyFulfillment');
	}

	public function shopifyOrderItem()
	{
		return $this->belongsTo('App\Models\Order\ShopifyOrderItem');
	}
}