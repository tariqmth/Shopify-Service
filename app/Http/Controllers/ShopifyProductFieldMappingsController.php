<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShopifyProductFieldMappingCollection as MappingCollectionResource;
use App\Models\ProductFields\RexProductFieldRepository;
use App\Models\ProductFields\ShopifyProductFieldRepository;
use Illuminate\Http\Request;
use App\Models\Store\ShopifyStore;
use App\Models\Client\Client;
use Illuminate\Support\Collection;
use Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShopifyProductFieldMappingsController extends Controller
{
    protected $shopifyProductFieldRepository;
    protected $rexProductFieldRepository;

    public function __construct(
        ShopifyProductFieldRepository $shopifyProductFieldRepository,
        RexProductFieldRepository $rexProductFieldRepository
    ) {
        $this->shopifyProductFieldRepository = $shopifyProductFieldRepository;
        $this->rexProductFieldRepository = $rexProductFieldRepository;
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
            'shopify_product_field_mappings' => 'required'
        ])->validate();

        $productFieldMappingsData = $data['shopify_product_field_mappings'];

        foreach ($productFieldMappingsData as $productFieldMappingData) {
            Validator::make($productFieldMappingData, [
                'shopify_product_field_name' => 'required',
                'rex_product_field_name' => 'nullable'
            ])->validate();

            $shopifyProductFieldName = $productFieldMappingData['shopify_product_field_name'];
            $shopifyProductField = $this->shopifyProductFieldRepository->get($shopifyProductFieldName);
            if (!isset($shopifyProductField)) {
                return response(
                    'Shopify product field ' . $shopifyProductFieldName . ' could not be found',
                    404
                );
            }

            $this->shopifyProductFieldRepository->createOrUpdateMapping(
                $shopifyStore->id,
                $shopifyProductField->id,
                $productFieldMappingData['rex_product_field_name']
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

        $mappings = $this->shopifyProductFieldRepository->getMappings($shopifyStore->id, true);

        return new MappingCollectionResource($mappings);
    }
}
