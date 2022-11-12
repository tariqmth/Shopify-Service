<?php

namespace App\Models\Customer;

class RexCustomerRepository
{
    public function create($rexSalesChannelId, $externalId = null, $email = null)
    {
        if (!isset($rexSalesChannelId)) {
            throw new \Exception('Cannot create Rex customer without Rex sales channel.');
        }

        if (isset($email)) {
            $existingCustomer = RexCustomer
                ::where('rex_sales_channel_id', $rexSalesChannelId)
                ->where('email', $email)
                ->first();
            if (isset($existingCustomer)) {
                throw new \Exception('There is already a customer for this sales channel with email ' . $email);
            }
        }

        $rexCustomer = new RexCustomer;
        $rexCustomer->rex_sales_channel_id = $rexSalesChannelId;
        $rexCustomer->external_id = $externalId;
        $rexCustomer->email = $email;
        $rexCustomer->save();
        return $rexCustomer;
    }

    public function getOrCreate($rexSalesChannelId, $externalId)
    {
        $rexCustomer = RexCustomer
            ::where('rex_sales_channel_id', $rexSalesChannelId)
            ->where('external_id', $externalId)
            ->first();

        if (!isset($rexCustomer)) {
            $rexCustomer = $this->create($rexSalesChannelId, $externalId);
        }

        return $rexCustomer;
    }

    public function getOrCreateForShopifyCustomer(ShopifyCustomer $shopifyCustomer)
    {
        if (isset($shopifyCustomer->rexCustomer)) {
            return $shopifyCustomer->rexCustomer;
        }

        $rexSalesChannelId = $shopifyCustomer->shopifyStore->rex_sales_channel_id;

        if (isset($shopifyCustomer->email)) {
            $rexCustomer = RexCustomer
                ::where('rex_sales_channel_id', $rexSalesChannelId)
                ->where('email', $shopifyCustomer->email)
                ->first();
        }

        if (!isset($rexCustomer)) {
            $rexCustomer = $this->create($rexSalesChannelId, null, $shopifyCustomer->email);
        }

        $shopifyCustomer->rexCustomer()->associate($rexCustomer);
        // Here constraint shopify_customers_rex_customer_id_unique failed when duplicate email exist
        $shopifyCustomer->save();
        return $rexCustomer;
    }
}