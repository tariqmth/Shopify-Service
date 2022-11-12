<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShopifyPaymentGatewayMappingCollection as MappingCollectionResource;
use App\Models\Payment\ShopifyPaymentGatewayRepository;
use Illuminate\Http\Request;
use App\Models\Store\ShopifyStore;
use App\Models\Client\Client;
use Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShopifyPaymentGatewayMappingsController extends Controller
{
    protected $shopifyPaymentGatewayRepository;

    public function __construct(
        ShopifyPaymentGatewayRepository $shopifyPaymentGatewayRepository
    ) {
        $this->shopifyPaymentGatewayRepository = $shopifyPaymentGatewayRepository;
    }

    public function put(Request $request, $clientId, $subdomain)
    {
        try {
            $shopifyStore = $this->findShopifyStore($clientId, $subdomain);
        } catch (ModelNotFoundException $e) {
            return response('Shopify store could not be found.', 404);
        }

        $data = json_decode($request->getContent(), true);

        Validator::make($data, [
            'payment_mappings' => 'required'
        ])->validate();

        $paymentMappingsData = $data['payment_mappings'];

        foreach ($paymentMappingsData as $paymentMappingData) {
            Validator::make($paymentMappingData, [
                'shopify_gateway_name' => 'required',
                'rex_payment_method_id' => 'integer|nullable'
            ])->validate();
            $this->shopifyPaymentGatewayRepository->createOrUpdateMapping(
                $shopifyStore->id,
                $paymentMappingData['shopify_gateway_name'],
                $paymentMappingData['rex_payment_method_id']
            );
        }

        return $this->getCollection($clientId, $subdomain);
    }

    public function all(Request $request, $clientId, $subdomain)
    {
        return $this->getCollection($clientId, $subdomain);
    }

    private function findShopifyStore($clientId, $subdomain)
    {
        $client = Client::where('external_id', $clientId)->firstOrFail();
        $shopifyStore = ShopifyStore
            ::where('client_id', $client->id)
            ->where('subdomain', $subdomain)
            ->firstOrFail();
        return $shopifyStore;
    }

    private function getCollection($clientId, $subdomain)
    {
        try {
            $shopifyStore = $this->findShopifyStore($clientId, $subdomain);
        } catch (ModelNotFoundException $e) {
             return response('Shopify store could not be found.', 404);
        }

        $shopifyPaymentGateways = $this->shopifyPaymentGatewayRepository->getAll();
        $shopifyPaymentGatewayMappings = $shopifyStore->shopifyPaymentGatewayMappings;

        return new MappingCollectionResource($shopifyPaymentGatewayMappings, $shopifyPaymentGateways);
    }
}
