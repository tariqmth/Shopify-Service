<?php

namespace App\Models\Payment;

use App\Models\Syncable;

class ShopifyTransaction extends Syncable
{
	public function shopifyOrder()
	{
		return $this->belongsTo('App\Models\Order\ShopifyOrder');
	}

	public function shopifyPaymentGateway()
	{
		return $this->belongsTo('App\Models\Payment\ShopifyPaymentGateway');
	}

	public function rexPayment()
	{
		return $this->belongsTo('App\Models\Payment\RexPayment');
	}
}
