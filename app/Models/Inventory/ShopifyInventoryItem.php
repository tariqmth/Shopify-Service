<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class ShopifyInventoryItem extends Model
{
	public function shopifyProductVariant()
	{
		return $this->belongsTo('App\Models\Product\ShopifyProductVariant');
	}
}
