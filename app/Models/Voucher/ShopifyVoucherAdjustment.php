<?php

namespace App\Models\Voucher;

use App\Models\Syncable;

class ShopifyVoucherAdjustment extends Syncable
{
    public function shopifyVoucher()
	{
		return $this->belongsTo('App\Models\Voucher\ShopifyVoucher');
	}

	public function rexVoucherRedemption()
	{
		return $this->belongsTo('App\Models\Voucher\RexVoucherRedemption');
	}
}