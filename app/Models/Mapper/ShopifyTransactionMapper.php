<?php

namespace App\Models\Mapper;

use App\Models\Store\ShopifyStore;
use RetailExpress\SkyLink\Sdk\Sales\Payments\Payment as RexPaymentData;

class ShopifyTransactionMapper
{
    public function getMappedData(RexPaymentData $rexPaymentData, ShopifyStore $shopifyStore)
    {
        return [
            "amount"   => $rexPaymentData->getTotal()->toNative(),
            "kind"     => "capture",
            "currency" => $shopifyStore->currency
        ];
    }
}
