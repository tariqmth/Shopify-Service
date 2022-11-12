<?php

namespace App\Models\Mapper;

use App\Models\Customer\RexCustomer;
use App\Models\Customer\ShopifyCustomer;
use RetailExpress\SkyLink\Sdk\Customers\CustomerId as RexCustomerIdData;
use RetailExpress\SkyLink\Sdk\Customers\NewsletterSubscription as RexNewsletterSubscriptionData;
use RetailExpress\SkyLink\Sdk\Customers\Customer as RexCustomerData;
use RetailExpress\SkyLink\Sdk\Customers\BillingContact as RexBillingContactData;
use RetailExpress\SkyLink\Sdk\Customers\ShippingContact as RexShippingContactData;

class ShopifyCustomerMapper extends Mapper
{
    public function getInitialMappedData(RexCustomerData $rexCustomerData)
    {
        return [
            'first_name'        => $this->getFirstName($rexCustomerData),
            'last_name'         => $this->getLastName($rexCustomerData),
            'email'             => $this->getEmail($rexCustomerData),
            'accepts_marketing' => $this->getAcceptsMarketing($rexCustomerData)
        ];
    }

    public function getMappedData(RexCustomerData $rexCustomerData)
    {
        return [
            'email'             => $this->getEmail($rexCustomerData),
            'accepts_marketing' => $this->getAcceptsMarketing($rexCustomerData)
        ];
    }

    protected function getFirstName(RexCustomerData $rexCustomerData)
    {
        return $rexCustomerData->getBillingContact()->getName()->getFirstName()->toNative();
    }

    protected function getLastName(RexCustomerData $rexCustomerData)
    {
        return $rexCustomerData->getBillingContact()->getName()->getLastName()->toNative();
    }

    protected function getEmail(RexCustomerData $rexCustomerData)
    {
        return $rexCustomerData->getBillingContact()->getEmailAddress()->toNative();
    }

    protected function getAcceptsMarketing(RexCustomerData $rexCustomerData)
    {
        return $rexCustomerData->getNewsletterSubscription()->toNative();
    }
}
