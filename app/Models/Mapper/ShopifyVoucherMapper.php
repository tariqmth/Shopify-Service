<?php

namespace App\Models\Mapper;

use App\Models\Order\RexOrder;
use App\Models\Voucher\RexVoucher;
use RetailExpress\SkyLink\Sdk\Vouchers\Voucher as RexVoucherData;

class ShopifyVoucherMapper extends Mapper
{
    public function getMappedData(RexVoucher $rexVoucher, RexVoucherData $rexVoucherData)
    {
        return [
            'initial_value' => $this->getInitialValue($rexVoucherData),
            'code'          => $this->getCode($rexVoucherData),
            'expires_on'    => $this->getExpiresOn($rexVoucherData),
            'order_id'      => $this->getOrderId($rexVoucher),
            'customer_id'   => $this->getCustomerId($rexVoucher)
        ];
    }

    protected function getInitialValue(RexVoucherData $rexVoucherData)
    {
        if ($rexVoucherData->getInitialValue() !== null && $rexVoucherData->getInitialValue() != 0) {
            return $rexVoucherData->getInitialValue();
        } else {
            return $rexVoucherData->getBalance()->toNative();
        }
    }

    protected function getCode(RexVoucherData $rexVoucherData)
    {
        return $rexVoucherData->getCode()->toNative();
    }

    protected function getExpiresOn(RexVoucherData $rexVoucherData)
    {
        if ($rexVoucherData->getExpiryDate() !== null) {
            return $rexVoucherData->getExpiryDate()->format('Y-m-d');
        } else {
            return null;
        }
    }

    protected function getOrderId(RexVoucher $rexVoucher)
    {
        if (isset($rexVoucher->rexOrder->shopifyOrder->external_id)) {
            return $rexVoucher->rexOrder->shopifyOrder->external_id;
        } else {
            return null;
        }
    }

    protected function getCustomerId(RexVoucher $rexVoucher)
    {
        if (isset($rexVoucher->rexOrder->shopifyOrder->shopifyCustomer->external_id)) {
            return $rexVoucher->rexOrder->shopifyOrder->shopifyCustomer->external_id;
        } else {
            return null;
        }
    }
}
