<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class ShopifyPaymentGateway extends Model
{
    protected function shopifyPaymentGatewayMappings()
	{
		return $this->hasMany('App\Models\Payment\ShopifyPaymentGatewayMapping');
	}
}
