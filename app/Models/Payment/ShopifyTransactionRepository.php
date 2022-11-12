<?php

namespace App\Models\Payment;

class ShopifyTransactionRepository
{
    protected $shopifyPaymentGatewayRepository;

    public function __construct(
        ShopifyPaymentGatewayRepository $shopifyPaymentGatewayRepository
    ) {
        $this->shopifyPaymentGatewayRepository = $shopifyPaymentGatewayRepository;
    }

    public function get($externalId)
    {
        return ShopifyTransaction
            ::where('external_id', $externalId)
            ->firstOrFail();

    }
    public function getOrCreate($shopifyOrderId, $shopifyPaymentGatewayId, $externalId)
    {
        $shopifyTransaction = ShopifyTransaction
            ::where('shopify_order_id', $shopifyOrderId)
            ->where('shopify_payment_gateway_id', $shopifyPaymentGatewayId)
            ->where('external_id', $externalId)
            ->first();

        if (!isset($shopifyTransaction)) {
            $shopifyTransaction = new ShopifyTransaction;
            $shopifyTransaction->shopify_order_id = $shopifyOrderId;
            $shopifyTransaction->external_id = $externalId;
            $shopifyTransaction->shopify_payment_gateway_id = $shopifyPaymentGatewayId;
            $shopifyTransaction->save();
        }

        return $shopifyTransaction;
    }

    public function getOrCreateForRexPayment(RexPayment $rexPayment)
    {
        if (isset($rexPayment->shopifyTransaction)) {
            return $rexPayment->shopifyTransaction;
        }

        $shopifyOrder = $rexPayment->rexOrder->shopifyOrder;

        $shopifyTransaction = new ShopifyTransaction;
        $shopifyTransaction->shopifyOrder()->associate($shopifyOrder);
        $shopifyTransaction->rexPayment()->associate($rexPayment);
        $shopifyTransaction->save();
        return $shopifyTransaction;
    }
}