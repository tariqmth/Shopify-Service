<?php

namespace App\Models\Mapper;

class ShopifyProductWithoutVariantsMapper extends Mapper
{
    public function getMappedData()
    {
        $slaveData = [
            'variants' => [
                [
                    'position' => 1,
                    'price' => 9999999
                ]
            ],
            'options' => [
                [
                    'name' => 'Title',
                    'position' => 1
                ]
            ]
        ];

        return $slaveData;
    }
}
