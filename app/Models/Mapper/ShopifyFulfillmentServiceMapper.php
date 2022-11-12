<?php

namespace App\Models\Mapper;

class ShopifyFulfillmentServiceMapper extends Mapper
{
    public function getMappedData()
    {
        $slaveData = [
            'name' => 'Retail Express',
            'inventory_management' => false,
            'tracking_support' => false,
            'requires_shipping_method' => true,
            'format' => 'json'
        ];

        return $slaveData;
    }
}
