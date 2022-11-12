<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class RexInventory extends Model
{
    protected $table = 'rex_inventory';

	public function rexProduct()
	{
		return $this->belongsTo('App\Models\Product\RexProduct');
	}

	public function rexOutlet()
	{
		return $this->belongsTo('App\Models\Location\RexOutlet');
	}
}
