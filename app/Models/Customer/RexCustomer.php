<?php

namespace App\Models\Customer;

use App\Models\Syncable;

class RexCustomer extends Syncable
{
    public function rexSalesChannel()
	{
		return $this->belongsTo('App\Models\Store\RexSalesChannel');
	}

	public function shopifyCustomer()
    {
        return $this->hasOne('App\Models\Customer\ShopifyCustomer');
    }
}