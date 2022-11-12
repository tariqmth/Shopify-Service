<?php

namespace App\Models\Mapper;

use RetailExpress\SkyLink\Sdk\Customers\BillingContact as RexBillingContactData;
use RetailExpress\SkyLink\Sdk\Customers\ShippingContact as RexShippingContactData;

class RexAddressMapperFromShopify
{
    public function getMappedBillingContact($shopifyBillingAddress, $emailAddress)
    {
        if (!isset($shopifyBillingAddress)) {
            return RexBillingContactData::fromNative('', '', $emailAddress);
        }

        $billingContactArray = [
            $shopifyBillingAddress->first_name,
            $shopifyBillingAddress->last_name,
            $emailAddress,
            $shopifyBillingAddress->company,
            $shopifyBillingAddress->address1,
            $shopifyBillingAddress->address2,
            $shopifyBillingAddress->city,
            $shopifyBillingAddress->province_code,
            $shopifyBillingAddress->zip,
            $shopifyBillingAddress->country,
            $shopifyBillingAddress->phone,
            null
        ];

        $billingContactArray = $this->replaceNullWithEmptyStrings($billingContactArray);
        $billingContactData = RexBillingContactData::fromNative(...$billingContactArray);
        return $billingContactData;
    }

    public function getMappedShippingContact($shopifyShippingAddress)
    {
        $shippingContactArray = [
            $shopifyShippingAddress->first_name,
            $shopifyShippingAddress->last_name,
            $shopifyShippingAddress->company,
            $shopifyShippingAddress->address1,
            $shopifyShippingAddress->address2,
            $shopifyShippingAddress->city,
            $shopifyShippingAddress->province_code,
            $shopifyShippingAddress->zip,
            $shopifyShippingAddress->country,
            $shopifyShippingAddress->phone
        ];

        $shippingContactArray = $this->replaceNullWithEmptyStrings($shippingContactArray);
        $shippingContactData = RexShippingContactData::fromNative(...$shippingContactArray);
        return $shippingContactData;
    }

    private function replaceNullWithEmptyStrings(array $array)
    {
        return array_map(function($value) {
            return isset($value) ? $value : '';
        }, $array);
    }
}