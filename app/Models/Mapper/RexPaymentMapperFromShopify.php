<?php

namespace App\Models\Mapper;

use App\Models\Payment\RexPayment;
use RetailExpress\SkyLink\Sdk\Sales\Payments\Payment as RexPaymentData;
use RetailExpress\SkyLink\Sdk\Sales\Payments\PaymentMethodId;

class RexPaymentMapperFromShopify extends Mapper
{
    const VOUCHER_PAYMENT_METHOD_ID = 11;

    public function getMappedData(RexPayment $rexPayment, $shopifyTransactionData, $voucherCode = null)
    {
        if (isset($voucherCode)) {
            RexPaymentData::setVoucherMethodId(PaymentMethodId::fromNative(self::VOUCHER_PAYMENT_METHOD_ID));
            $rexPaymentData = RexPaymentData::usingVoucherWithCodeFromNative(
                $this->getOrderId($rexPayment),
                $this->getMadeAt($shopifyTransactionData),
                $voucherCode,
                $this->getTotal($shopifyTransactionData)
            );
        } else {
            $rexPaymentData = RexPaymentData::normalFromNative(
                $this->getOrderId($rexPayment),
                $this->getMadeAt($shopifyTransactionData),
                $this->getMethodId($rexPayment),
                $this->getTotal($shopifyTransactionData)
            );
        }

        return $rexPaymentData;
    }

    protected function getOrderId(RexPayment $rexPayment)
    {
        if (!$rexPayment->rexOrder->hasBeenSynced()) {
            throw new \Exception('Cannot map external order ID for Rex order that has not been synced.');
        }
        return $rexPayment->rexOrder->external_id;
    }

    protected function getMadeAt($shopifyTransactionData)
    {
        $dateTime = new \DateTime($shopifyTransactionData->created_at);
        return $dateTime->getTimestamp();
    }

    protected function getMethodId(RexPayment $rexPayment)
    {
        return $rexPayment->rex_payment_method_external_id;
    }

    protected function getTotal($shopifyTransactionData)
    {
        return $shopifyTransactionData->amount;
    }
}
