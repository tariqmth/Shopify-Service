<?php

namespace App\Models\Customer;

use App\Models\Syncable;

class ShopifyCustomer extends Syncable
{
    public function shopifyStore()
	{
		return $this->belongsTo('App\Models\Store\ShopifyStore');
	}

    public function rexCustomer()
	{
		return $this->belongsTo('App\Models\Customer\RexCustomer');
	}

	public function shopifyOrders()
	{
		return $this->hasMany('App\Models\Order\ShopifyOrder');
	}
}