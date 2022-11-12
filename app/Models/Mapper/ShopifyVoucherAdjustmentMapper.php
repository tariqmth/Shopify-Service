<?php

namespace App\Models\Mapper;

use App\Models\Voucher\RexVoucherRedemption;

class ShopifyVoucherAdjustmentMapper extends Mapper
{
    public function getMappedData(RexVoucherRedemption $rexVoucherRedemption)
    {
        return [
            'amount' => $this->getAmount($rexVoucherRedemption)
        ];
    }

    protected function getAmount($rexVoucherRedemption)
    {
        return '-' . $rexVoucherRedemption->amount;
    }
}
