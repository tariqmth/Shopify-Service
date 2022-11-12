<?php

namespace App\Models\Customer;

class ShopifyCustomerRepository
{
    public function create($shopifyStoreId, $externalId = null)
    {
        if (!isset($shopifyStoreId)) {
            throw new \Exception('Cannot create Shopify customer without Shopify store.');
        }

        $shopifyCustomer = new ShopifyCustomer;
        $shopifyCustomer->shopify_store_id = $shopifyStoreId;
        $shopifyCustomer->external_id = $externalId;
        $shopifyCustomer->save();
        return $shopifyCustomer;
    }

    public function getOrCreate($shopifyStoreId, $externalId)
    {
        $shopifyCustomer = ShopifyCustomer
            ::where('shopify_store_id', $shopifyStoreId)
            ->where('external_id', $externalId)
            ->first();

        if (!isset($shopifyCustomer)) {
            $shopifyCustomer = $this->create($shopifyStoreId, $externalId);
        }

        return $shopifyCustomer;
    }

    public function getOrCreateForRexCustomer(RexCustomer $rexCustomer)
    {
        if (isset($rexCustomer->shopifyCustomer)) {
            return $rexCustomer->shopifyCustomer;
        }

        $shopifyStore = $rexCustomer->rexSalesChannel->shopifyStore;

        if (!isset($shopifyStore)) {
            throw new \Exception('Cannot create Shopify customer for Rex customer '
                . 'without associated Shopify store.');
        }

        $existingCustomerByEmail = ShopifyCustomer
            ::where('shopify_store_id', $shopifyStore->id)
            ->whereNotNull('email')
            ->where('email', $rexCustomer->email)
            ->whereNull('rex_customer_id')
            ->first();

        if (isset($existingCustomerByEmail)) {
            $existingCustomerByEmail->rexCustomer()->associate($rexCustomer);
            $existingCustomerByEmail->save();
            return $existingCustomerByEmail;
        }

        $shopifyCustomer = $this->create($shopifyStore->id);
        $shopifyCustomer->email = $rexCustomer->email;
        $shopifyCustomer->rexCustomer()->associate($rexCustomer);
        $shopifyCustomer->save();
        return $shopifyCustomer;
    }
}