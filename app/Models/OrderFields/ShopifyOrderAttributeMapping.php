<?php

namespace App\Models\OrderFields;

use Illuminate\Database\Eloquent\Model;

class ShopifyOrderAttributeMapping extends Model
{
	public function shopifyStore()
	{
		return $this->belongsTo('App\Models\Store\ShopifyStore');
	}
}
