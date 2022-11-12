<?php

namespace App\Models\Store;

class StoreRepository
{
    public function getRexSalesChannel($clientId, $salesChannelId)
    {
        return RexSalesChannel::where('client_id', $clientId)
            ->where('sales_channel', $salesChannelId)
            ->first();
    }
}