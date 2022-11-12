<?php

namespace App\Models\Mapper;

use App\Models\Customer\RexCustomer;
use RetailExpress\SkyLink\Sdk\Customers\CustomerId as RexCustomerIdData;
use RetailExpress\SkyLink\Sdk\Customers\NewsletterSubscription as RexNewsletterSubscriptionData;
use RetailExpress\SkyLink\Sdk\Customers\Customer as RexCustomerData;
use RetailExpress\SkyLink\Sdk\Customers\BillingContact as RexBillingContactData;
use RetailExpress\SkyLink\Sdk\Customers\ShippingContact as RexShippingContactData;

class RexCustomerMapperFromShopify extends Mapper
{
    protected $rexAddressMapperFromShopify;

    public function __construct(
        RexAddressMapperFromShopify $rexAddressMapperFromShopify
    ) {
        $this->rexAddressMapperFromShopify = $rexAddressMapperFromShopify;
    }

    public function getMappedData(RexCustomer $rexCustomer, $shopifyCustomerData)
    {
        $rexCustomerData = new RexCustomerData(
            $this->getBillingContact($shopifyCustomerData),
            $this->getShippingContact($shopifyCustomerData),
            $this->getNewsletterSubscription($shopifyCustomerData),
            $this->getCustomerId($rexCustomer),
            null,
            false
        );

        return $rexCustomerData;
    }

    protected function getCustomerId(RexCustomer $rexCustomer)
    {
        return isset($rexCustomer->external_id) ? RexCustomerIdData::fromNative($rexCustomer->external_id) : null;
    }

    protected function getBillingContact($shopifyCustomerData)
    {
        $email = $shopifyCustomerData->email;
        if (isset($shopifyCustomerData->default_address)) {
            return $this->rexAddressMapperFromShopify->getMappedBillingContact(
                $shopifyCustomerData->default_address,
                $shopifyCustomerData->email
            );
        } else {
            $firstName = isset($shopifyCustomerData->first_name) ? $shopifyCustomerData->first_name : '';
            $lastName = isset($shopifyCustomerData->last_name) ? $shopifyCustomerData->last_name : '';
            return RexBillingContactData::fromNative($firstName, $lastName, $email);
        }
    }

    protected function getShippingContact($shopifyCustomerData)
    {
        if (isset($shopifyCustomerData->default_address)) {
            $defaultAddress = $shopifyCustomerData->default_address;
            return $this->rexAddressMapperFromShopify->getMappedShippingContact($defaultAddress);
        } else {
            return RexShippingContactData::fromNative();
        }
    }

    protected function getNewsletterSubscription($shopifyCustomerData)
    {
        return new RexNewsletterSubscriptionData($shopifyCustomerData->accepts_marketing);
    }
}
