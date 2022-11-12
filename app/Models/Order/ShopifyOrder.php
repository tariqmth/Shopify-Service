<?php

namespace App\Models\Order;

use App\Models\Syncable;

class ShopifyOrder extends Syncable
{
    public function shopifyStore()
	{
		return $this->belongsTo('App\Models\Store\ShopifyStore');
	}

    public function shopifyCustomer()
	{
		return $this->belongsTo('App\Models\Customer\ShopifyCustomer');
	}

	public function rexOrder()
	{
		return $this->belongsTo('App\Models\Order\RexOrder');
	}

	public function shopifyFulfillments()
	{
		return $this->hasMany('App\Models\Fulfillment\ShopifyFulfillment');
	}

	public function shopifyOrderItems()
    {
        return $this->hasMany('App\Models\Order\ShopifyOrder');
    }
}