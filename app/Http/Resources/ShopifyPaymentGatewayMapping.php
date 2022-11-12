<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShopifyPaymentGatewayMapping extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'payment_mapping';

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'shopify_gateway_name' => $this->shopifyPaymentGateway->name,
            'shopify_gateway_label' => $this->shopifyPaymentGateway->label,
            'rex_payment_method_id' => $this->rex_payment_method_external_id
        ];
    }
}
