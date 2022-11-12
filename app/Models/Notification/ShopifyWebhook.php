<?php

namespace App\Models\Notification;

use App\Models\Syncable;

class ShopifyWebhook extends Syncable
{
    public function shopifyStore()
	{
		return $this->belongsTo('App\Models\Store\ShopifyStore');
	}
}
