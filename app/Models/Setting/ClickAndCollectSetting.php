<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Model;

class ClickAndCollectSetting extends Model
{
	public function addonLicense()
	{
		return $this->belongsTo('App\Models\License\AddonLicense');
	}

	public function shopifyStore()
	{
		return $this->belongsTo('App\Models\Store\ShopifyStore');
	}
}
