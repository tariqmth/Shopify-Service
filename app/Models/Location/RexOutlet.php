<?php

namespace App\Models\Location;

use App\Models\Syncable;

class RexOutlet extends Syncable
{
    public function rexSalesChannel()
	{
		return $this->belongsTo('App\Models\Store\RexSalesChannel');
	}

	public function rexInventory()
	{
		return $this->hasMany('App\Models\Inventory\RexInventory');
	}
}