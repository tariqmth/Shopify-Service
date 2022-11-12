<?php

namespace App\Models\Attribute;

use Illuminate\Database\Eloquent\Model;

class RexAttribute extends Model
{
    protected $fillable = array('client_id', 'name');

    /**
	 * Get client that attribute belongs to
	 *
	 * @return Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function client()
	{
		return $this->belongsTo('App\Models\Client\Client', 'client_id');
	}

	public function rexAttributeOption()
	{
		return $this->hasMany('App\Models\Attribute\RexAttributeOption');
	}
}
