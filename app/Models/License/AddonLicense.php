<?php

namespace App\Models\License;

use Illuminate\Database\Eloquent\Model;

class AddonLicense extends Model
{
    const VALID_LICENSE_NAMES = [
        'gift_vouchers',
        'click_and_collect'
    ];

	public function client()
	{
		return $this->belongsTo('App\Models\Client\Client');
	}

	public function clickAndCollectSetting()
    {
        return $this->hasMany('App\Models\Setting\ClickAndCollectSetting');
    }
}
