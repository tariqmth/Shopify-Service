<?php

namespace App\Models\Store;

class RexSalesChannel extends Store
{
    protected $fillable = array('client_id', 'external_id');

    /**
	 * One to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\hasMany
	 */
	public function products()
	{
		return $this->hasMany('App\Models\Product\RexProduct');
	}

	public function shopifyStore()
	{
		return $this->hasOne('App\Models\Store\ShopifyStore');
	}

	public function getAssociatedStoresAttribute()
	{
		return collect([$this->shopifyStore]);
	}

	public function rexOutlets()
	{
		return $this->hasMany('App\Models\Location\RexOutlet');
	}

	public function rexCustomers()
	{
		return $this->hasMany('App\Models\Customer\RexCustomer');
	}

	public function rexOrders()
	{
		return $this->hasMany('App\Models\Order\RexOrder');
	}

	public function rexProductGroups()
	{
		return $this->hasMany('App\Models\Product\RexProductGroup');
	}

	public function rexVouchers()
	{
		return $this->hasMany('App\Models\Voucher\RexVoucher');
	}

	public function deleteAllChildren()
    {
        $this->rexOutlets()->delete();
        $this->rexOrders()->delete();
        $this->rexCustomers()->delete();
        $this->products()->delete();
        $this->rexProductGroups()->delete();
        $this->rexVouchers()->delete();
    }
}
