<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;
use App\Models\Source\SourceResolver;

abstract class Store extends Model
{
	public function client()
	{
		return $this->belongsTo('App\Models\Client\Client');
	}
}
