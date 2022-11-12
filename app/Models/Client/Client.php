<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = array('external_id', 'name');

    /**
	 * One to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\hasMany
	 */
	public function rexSalesChannels()
	{
		return $this->hasMany('App\Models\Store\RexSalesChannel');
	}

	/**
	 * One to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\hasMany
	 */
	public function shopifyStores()
	{
		return $this->hasMany('App\Models\Store\ShopifyStore');
	}

	public function getForeignStoresAttribute()
    {
        return $this->shopifyStores;
    }

    public function apiAuth()
	{
		return $this->hasOne('App\Models\ApiAuth');
	}

	public function addonLicenses()
    {
        return $this->hasMany('App\Models\License\AddonLicense');
    }
}
