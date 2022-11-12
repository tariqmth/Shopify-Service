<?php

namespace App\Models\Order;

use App\Models\Syncable;

class RexOrder extends Syncable
{
    public function rexSalesChannel()
	{
		return $this->belongsTo('App\Models\Store\RexSalesChannel');
	}

    public function rexCustomer()
	{
		return $this->belongsTo('App\Models\Customer\RexCustomer');
	}

	public function rexFulfillmentBatches()
	{
		return $this->hasMany('App\Models\Fulfillment\RexFulfillmentBatch');
	}

	public function rexOrderProducts()
    {
        return $this->hasMany('App\Models\Order\RexOrderProduct');
    }

	public function shopifyOrder()
    {
        return $this->hasOne('App\Models\Order\ShopifyOrder');
    }
}