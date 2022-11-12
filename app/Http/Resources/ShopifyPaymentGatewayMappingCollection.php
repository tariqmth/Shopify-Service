<?php

namespace App\Http\Resources;

use App\Models\Payment\ShopifyPaymentGatewayRepository;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Database\Eloquent\Collection;

class ShopifyPaymentGatewayMappingCollection extends ResourceCollection
{
    protected $shopifyPaymentGateways;

    public function __construct(
        Collection $resource,
        Collection $shopifyPaymentGateways
    ) {
        parent::__construct($resource);
        $this->shopifyPaymentGateways = $shopifyPaymentGateways;
    }

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'payment_mappings';

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $mappings = $this->collection;
        foreach ($this->shopifyPaymentGateways as $paymentGateway) {
            if ($mappings->where('shopify_payment_gateway_id', $paymentGateway->id)->count() < 1) {
                $emptyMapping = [
                    'shopify_gateway_name' => $paymentGateway->name,
                    'shopify_gateway_label' => $paymentGateway->label,
                    'rex_payment_method_id' => null
                ];
                $mappings->push($emptyMapping);
            }
        }
        return $mappings;
    }
}
