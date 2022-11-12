<?php

namespace App\Models\Order;

use App\Models\Syncable;

class RexOrderProduct extends Syncable
{
    public function rexOrder()
	{
		return $this->belongsTo('App\Models\Order\RexOrder');
	}

	public function rexProduct()
	{
		return $this->belongsTo('App\Models\Product\RexProduct');
	}

	public function shopifyOrderItem()
    {
        return $this->hasOne('App\Models\Order\ShopifyOrderItem');
    }

    public function rexOrderItems()
    {
        return $this->hasMany('App\Models\Order\RexOrderItem');
    }
}