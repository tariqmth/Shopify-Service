<?php

namespace App\Models\Payment;

use App\Models\Store\ShopifyStore;

class ShopifyPaymentGatewayRepository
{
    const LABELS = [
        'default' => 'Default',
        'shopify_payments' => 'Shopify Payments',
        'paypal' => 'Paypal',
        'eway' => 'eWay',
        'zip_pay' => 'ZipPay'
    ];

    public function initialize()
    {
        foreach (self::LABELS as $name => $label) {
            $this->createOrUpdate($name, $label);
        }
    }

    public function get($name)
    {
        return ShopifyPaymentGateway::where('name', $name)->first();
    }

    public function getAll()
    {
        return ShopifyPaymentGateway::all();
    }

    public function createOrUpdate($name, $label)
    {
        $shopifyPaymentGateway = $this->get($name);

        if ($shopifyPaymentGateway === null) {
            $shopifyPaymentGateway = new ShopifyPaymentGateway;
            $shopifyPaymentGateway->name = $name;
        }

        $shopifyPaymentGateway->label = $label;
        $shopifyPaymentGateway->save();
        return $shopifyPaymentGateway;
    }

    public function getMapping($shopifyStoreId, $shopifyPaymentGatewayName)
    {
        return ShopifyPaymentGatewayMapping
            ::where('shopify_store_id', $shopifyStoreId)
            ->whereHas('shopifyPaymentGateway', function($q) use ($shopifyPaymentGatewayName) {
                $q->where('name', $shopifyPaymentGatewayName);
            })
            ->first();
    }

    public function getMappingByRexPaymentMethod($shopifyStoreId, $rexPaymentMethodExternalId)
    {
        return ShopifyPaymentGatewayMapping
            ::where('shopify_store_id', $shopifyStoreId)
            ->where('rex_payment_method_external_id', $rexPaymentMethodExternalId)
            ->first();
    }

    public function getDefaultMapping($shopifyStoreId)
    {
        return $this->getMapping($shopifyStoreId, 'default');
    }

    public function getAllMappingsForStore($shopifyStoreId)
    {
        return ShopifyPaymentGatewayMapping
            ::where('shopify_store_id', $shopifyStoreId)
            ->all();
    }

    public function createOrUpdateMapping(
        $shopifyStoreId,
        $shopifyPaymentGatewayName,
        $rexPaymentMethodExternalId = null
    ) {
        $shopifyPaymentGateway = $this->get($shopifyPaymentGatewayName);

        if ($shopifyPaymentGateway === null) {
            throw new \Exception('Cannot create mapping for non-existant Shopify payment gateway.');
        }

        $shopifyPaymentGatewayMapping = $this->getMapping($shopifyStoreId, $shopifyPaymentGatewayName);

        if ($shopifyPaymentGatewayMapping === null) {
            $shopifyPaymentGatewayMapping = new ShopifyPaymentGatewayMapping;
            $shopifyPaymentGatewayMapping->shopify_store_id = $shopifyStoreId;
            $shopifyPaymentGatewayMapping->shopify_payment_gateway_id = $shopifyPaymentGateway->id;
        }

        $shopifyPaymentGatewayMapping->rex_payment_method_external_id = $rexPaymentMethodExternalId;
        $shopifyPaymentGatewayMapping->save();
        return $shopifyPaymentGatewayMapping;
    }
}