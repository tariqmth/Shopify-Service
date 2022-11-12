<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShopifyOrderAttributeMappingCollection as MappingCollectionResource;
use App\Models\OrderFields\ShopifyOrderAttributeRepository;
use Illuminate\Http\Request;
use App\Models\Store\ShopifyStore;
use App\Models\Client\Client;
use Illuminate\Support\Collection;
use Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShopifyOrderAttributeMappingsController extends Controller
{
    protected $shopifyOrderAttributeRepository;

    public function __construct(
        ShopifyOrderAttributeRepository $shopifyOrderAttributeRepository
    ) {
        $this->shopifyOrderAttributeRepository = $shopifyOrderAttributeRepository;
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
            'shopify_order_attribute_mappings' => 'required'
        ])->validate();

        $orderAttributeMappingsData = $data['shopify_order_attribute_mappings'];

        foreach ($orderAttributeMappingsData as $orderAttributeMappingData) {
            Validator::make($orderAttributeMappingData, [
                'shopify_order_attribute' => 'required',
                'rex_order_field' => 'nullable'
            ])->validate();

            $this->shopifyOrderAttributeRepository->createOrUpdateMapping(
                $shopifyStore->id,
                $orderAttributeMappingData['shopify_order_attribute'],
                $orderAttributeMappingData['rex_order_field']
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

        $mappings = $this->shopifyOrderAttributeRepository->getMappings($shopifyStore->id);

        return new MappingCollectionResource($mappings);
    }
}
