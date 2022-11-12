<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShopifyProductFieldMapping extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'shopify_product_field_mapping';

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'shopify_product_field_name' => $this->shopifyProductField->name,
            'shopify_product_field_label' => $this->shopifyProductField->label,
            'rex_product_field_name' => $this->rex_product_field_name
        ];
    }
}
