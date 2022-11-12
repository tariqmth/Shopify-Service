<?php

namespace App\Models\Location;

use App\Models\Syncable;

class ShopifyFulfillmentService extends Syncable
{
    public function shopifyStore()
	{
		return $this->belongsTo('App\Models\Store\ShopifyStore');
	}

	public function shopifyLocation()
	{
		return $this->hasOne('App\Models\Location\ShopifyLocation');
	}
}
