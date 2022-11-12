<?php

namespace App\Models\Voucher;

use App\Models\Syncable;

class ShopifyVoucher extends Syncable
{
    public function shopifyStore()
	{
		return $this->belongsTo('App\Models\Store\ShopifyStore');
	}

	public function rexVoucher()
    {
        return $this->belongsTo('App\Models\Voucher\RexVoucher');
    }

    public function shopifyVoucherAdjustment()
    {
        return $this->hasOne('App\Models\Voucher\ShopifyVoucherAdjustment');
    }
}