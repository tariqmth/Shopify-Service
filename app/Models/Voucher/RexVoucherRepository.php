<?php

namespace App\Models\Voucher;

class RexVoucherRepository
{
    public function get($rexSalesChannelId, $externalId)
    {
        return RexVoucher
            ::where('rex_sales_channel_id', $rexSalesChannelId)
            ->where('external_id', $externalId)
            ->first();
    }

    public function create($rexSalesChannelId, $externalId = null)
    {
        $rexVoucher = new RexVoucher;
        $rexVoucher->rex_sales_channel_id = $rexSalesChannelId;
        $rexVoucher->external_id = $externalId;
        $rexVoucher->save();
        return $rexVoucher;
    }

    public function getRedemption($rexVoucherId, $rexPaymentExternalId)
    {
        return RexVoucherRedemption
            ::where('rex_voucher_id', $rexVoucherId)
            ->where('rex_payment_external_id', $rexPaymentExternalId)
            ->first();
    }

    public function createRedemption($rexVoucherId, $rexPaymentExternalId, $amount)
    {
        $rexVoucherRedemption = new RexVoucherRedemption;
        $rexVoucherRedemption->rex_voucher_id = $rexVoucherId;
        $rexVoucherRedemption->rex_payment_external_id = $rexPaymentExternalId;
        $rexVoucherRedemption->amount = $amount;
        $rexVoucherRedemption->save();
        return $rexVoucherRedemption;
    }
}