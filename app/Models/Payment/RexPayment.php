<?php

namespace App\Models\Payment;

use App\Models\Syncable;

class RexPayment extends Syncable
{
	public function rexOrder()
	{
		return $this->belongsTo('App\Models\Order\RexOrder');
	}

	public function shopifyTransaction()
    {
        return $this->hasOne('App\Models\Payment\ShopifyTransaction');
    }
}
