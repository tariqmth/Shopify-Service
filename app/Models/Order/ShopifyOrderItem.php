<?php

namespace App\Models\Order;

use App\Models\Syncable;

class ShopifyOrderItem extends Syncable
{
    public function shopifyOrder()
	{
		return $this->belongsTo('App\Models\Order\ShopifyOrder');
	}

	public function rexOrderProduct()
    {
        return $this->belongsTo('App\Models\Order\RexOrderProduct');
    }

    public function shopifyFulfillmentItems()
    {
        return $this->hasMany('App\Models\Fulfillment\ShopifyFulfillmentItem');
    }
}