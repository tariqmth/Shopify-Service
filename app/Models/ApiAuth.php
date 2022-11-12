<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ApiAuth extends Authenticatable
{
    protected $table = "api_auth";

	public function source()
	{
		return $this->belongsTo('App\Models\Source\Source');
	}

    public function matchesSource($code)
    {
        return isset($this->source) && $this->source->code == $code;
    }
}
