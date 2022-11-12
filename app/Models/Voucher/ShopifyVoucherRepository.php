<?php

namespace App\Models\Voucher;

use App\Exceptions\ImpossibleTaskException;

class ShopifyVoucherRepository
{
    public function get($shopifyStoreId, $externalId)
    {
        return ShopifyVoucher
            ::where('shopify_store_id', $shopifyStoreId)
            ->where('external_id', $externalId)
            ->first();
    }

    public function create($shopifyStoreId, $externalId = null, $rexVoucherId = null)
    {
        $shopifyVoucher = new ShopifyVoucher;
        $shopifyVoucher->shopify_store_id = $shopifyStoreId;
        $shopifyVoucher->external_id = $externalId;
        $shopifyVoucher->rex_voucher_id = $rexVoucherId;
        $shopifyVoucher->save();
        return $shopifyVoucher;
    }

    public function createForRexVoucher(RexVoucher $rexVoucher)
    {
        $shopifyStore = $rexVoucher->rexSalesChannel->shopifyStore;

        if (!isset($shopifyStore)) {
            throw new ImpossibleTaskException('No Shopify store is set for Rex voucher\'s sales channel.');
        }

        return $this->create($shopifyStore->id, null, $rexVoucher->id);
    }

    public function getAdjustment($shopifyVoucherId, $rexVoucherRedemptionId)
    {
        return ShopifyVoucherAdjustment
            ::where('shopify_voucher_id', $shopifyVoucherId)
            ->where('rex_voucher_redemption_id', $rexVoucherRedemptionId)
            ->first();
    }

    public function createAdjustment($shopifyVoucherId, $rexVoucherRedemptionId)
    {
        $shopifyVoucherAdjustment = new ShopifyVoucherAdjustment;
        $shopifyVoucherAdjustment->shopify_voucher_id = $shopifyVoucherId;
        $shopifyVoucherAdjustment->rex_voucher_redemption_id = $rexVoucherRedemptionId;
        $shopifyVoucherAdjustment->save();
        return $shopifyVoucherAdjustment;
    }
}