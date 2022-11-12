<?php

namespace App\Models\Mapper;

use App\Models\Notification\ShopifyWebhook;

class ShopifyWebhookMapper extends Mapper
{
    public function getMappedData(ShopifyWebhook $shopifyWebhook)
    {
        $slaveData = [
            'topic' => $shopifyWebhook->topic,
            'address' => $shopifyWebhook->address,
            'format' => 'json'
        ];

        return $slaveData;
    }
}
