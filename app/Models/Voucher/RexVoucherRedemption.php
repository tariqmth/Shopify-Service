<?php

namespace App\Models\Voucher;

use App\Models\Syncable;

class RexVoucherRedemption extends Syncable
{
    public function rexVoucher()
	{
		return $this->belongsTo('App\Models\Voucher\RexVoucher');
	}

	public function shopifyVoucherAdjustment()
    {
        return $this->hasOne('App\Models\Voucher\ShopifyVoucherAdjustment');
    }
}