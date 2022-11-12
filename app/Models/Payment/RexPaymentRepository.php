<?php

namespace App\Models\Payment;

use App\Models\Order\RexOrder;

class RexPaymentRepository
{
    protected $shopifyPaymentGatewayRepository;

    public function __construct(
        ShopifyPaymentGatewayRepository $shopifyPaymentGatewayRepository
    ) {
        $this->shopifyPaymentGatewayRepository = $shopifyPaymentGatewayRepository;
    }

    public function getOrCreate(ShopifyTransaction $shopifyTransaction)
    {
        if (isset($shopifyTransaction->rexPayment)) {
            return $shopifyTransaction->rexPayment;
        }

        $rexOrder = $shopifyTransaction->shopifyOrder->rexOrder;

        if (!isset($rexOrder)) {
            throw new \Exception('Cannot create Rex payment for Shopify transaction where '
                . 'Shopify order is not associated with Rex order.');
        }

        $shopifyStoreId = $shopifyTransaction->shopifyOrder->shopify_store_id;

        $shopifyPaymentGatewayMapping = $this->shopifyPaymentGatewayRepository->getMapping(
            $shopifyStoreId,
            $shopifyTransaction->shopifyPaymentGateway->name
        );

        if (!isset($shopifyPaymentGatewayMapping)
            || !isset($shopifyPaymentGatewayMapping->rex_payment_method_external_id)
        ) {
            $shopifyPaymentGatewayMapping = $this->shopifyPaymentGatewayRepository->getDefaultMapping($shopifyStoreId);
        }

        if (!isset($shopifyPaymentGatewayMapping)) {
            throw new \Exception('No default Shopify payment gateway mapping available.');
        }

        $rexPayment = new RexPayment;
        $rexPayment->rexOrder()->associate($rexOrder);
        $rexPayment->rex_payment_method_external_id = $shopifyPaymentGatewayMapping->rex_payment_method_external_id;
        $rexPayment->save();

        $shopifyTransaction->rexPayment()->associate($rexPayment);
        $shopifyTransaction->save();

        return $rexPayment;
    }

    public function getOrCreateByExternalId(RexOrder $rexOrder, $externalId, $rexPaymentMethodExternalId)
    {
        $rexPayment = RexPayment
            ::where('rex_order_id', $rexOrder->id)
            ->where('external_id', $externalId)
            ->first();

        if (!isset($rexPayment)) {
            $rexPayment = new RexPayment;
            $rexPayment->rexOrder()->associate($rexOrder);
            $rexPayment->external_id = $externalId;
            $rexPayment->rex_payment_method_external_id = $rexPaymentMethodExternalId;
            $rexPayment->save();
        }

        return $rexPayment;
    }
}