<?php

namespace App\Models\Attribute;

use Illuminate\Database\Eloquent\Model;

class RexAttributeOption extends Model
{
    protected $fillable = array('rex_attribute_id', 'option_id', 'value');

    /**
	 * Get attribute that option belongs to
	 *
	 * @return Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function rexAttribute()
	{
		return $this->belongsTo('App\Models\Attribute\RexAttribute', 'rex_attribute_id');
	}
}