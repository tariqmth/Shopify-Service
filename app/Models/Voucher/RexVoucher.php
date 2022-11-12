<?php

namespace App\Models\Voucher;

use App\Models\Syncable;

class RexVoucher extends Syncable
{
    public function rexSalesChannel()
	{
		return $this->belongsTo('App\Models\Store\RexSalesChannel');
	}

	public function shopifyVoucher()
    {
        return $this->hasOne('App\Models\Voucher\ShopifyVoucher');
    }

    public function rexOrder()
    {
        return $this->belongsTo('App\Models\Order\RexOrder');
    }

    public function rexVoucherRedemption()
    {
        return $this->hasOne('App\Models\Voucher\RexVoucherRedemption');
    }
}