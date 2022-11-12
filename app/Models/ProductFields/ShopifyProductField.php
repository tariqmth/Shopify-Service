<?php

namespace App\Models\ProductFields;

use Illuminate\Database\Eloquent\Model;

class ShopifyProductField extends Model
{
	public function shopifyProductFieldMappings()
	{
		return $this->hasMany('App\Models\ProductFields\ShopifyProductFieldMappings');
	}
}
