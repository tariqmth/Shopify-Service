<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class ShopifyPaymentGatewayMapping extends Model
{
    public function shopifyStore()
	{
		return $this->belongsTo('App\Models\Store\ShopifyStore');
	}

	public function shopifyPaymentGateway()
	{
		return $this->belongsTo('App\Models\Payment\ShopifyPaymentGateway');
	}
}
