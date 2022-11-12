<?php

namespace App\Models\Order;

use App\Models\Syncable;

class RexOrderItem extends Syncable
{
    public function rexOrderProduct()
	{
		return $this->belongsTo('App\Models\Order\RexOrderProduct');
	}
}