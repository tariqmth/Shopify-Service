<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShopifyStore extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'shopify_store';

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $array = [
            'client_id' => $this->client->external_id,
            'subdomain' => $this->subdomain,
            'access_token' => $this->access_token,
            'api_key' => $this->api_key,
            'sales_channel_id' => $this->rexSalesChannel->external_id,
            'enabled' => $this->enabled ? true : false,
            'setup_status' => $this->setup_status,
            'full_domain' => $this->full_domain,
            'inventory_buffer' => $this->inventory_buffer,
            'preorders' => $this->preorders
        ];

        return $array;
    }
}
