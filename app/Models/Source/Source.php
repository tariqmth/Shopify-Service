<?php

namespace App\Models\Source;

use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    public function apiAuth()
	{
		return $this->hasOne('App\Models\ApiAuth');
	}
}
