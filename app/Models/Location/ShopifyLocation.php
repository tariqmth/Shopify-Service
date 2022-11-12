<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Model;

class ShopifyLocation extends Model
{
    public function shopifyStore()
	{
		return $this->belongsTo('App\Models\Store\ShopifyStore');
	}

	public function shopifyFulfillmentService()
	{
		return $this->belongsTo('App\Models\Location\ShopifyFulfillmentService');
	}
}
