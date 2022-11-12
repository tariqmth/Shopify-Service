<?php

namespace App\Models\ProductFields;

use Illuminate\Database\Eloquent\Model;

class ShopifyProductFieldMapping extends Model
{
    public function rexProductField()
	{
		return $this->belongsTo('App\Models\ProductFields\RexProductField');
	}

	public function shopifyProductField()
	{
		return $this->belongsTo('App\Models\ProductFields\ShopifyProductField');
	}

	public function shopifyStore()
	{
		return $this->belongsTo('App\Models\Store\ShopifyStore');
	}
}
