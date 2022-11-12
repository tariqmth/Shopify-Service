<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShopifyVariant extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'shopify_product_variant';

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $array = [
            'shopify_product_id' => $this->shopify_product_external_id,
            'shopify_product_title' => $this->title,
            'shopify_variant_id' => $this->external_id,
            'shopify_variant_sku' => $this->sku,
            'rex_product_id' => $this->rex_product_external_id
        ];

        return $array;
    }
}
