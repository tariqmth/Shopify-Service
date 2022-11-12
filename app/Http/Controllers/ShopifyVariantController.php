<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShopifyVariantCollection as ShopifyVariantCollection;
use App\Models\Client\Client;
use App\Models\Product\ShopifyProductVariant;
use Illuminate\Http\Request;
use App\Models\Store\ShopifyStore;
use Validator;

class ShopifyVariantController extends Controller
{
    public function all(Request $request, $clientId, $storeSubdomain)
    {
        $client = Client::where('external_id', $clientId)->firstOrFail();
        $shopifyStore = ShopifyStore
            ::where('client_id', $client->id)
            ->where('subdomain', $storeSubdomain)
            ->firstOrFail();
        $shopifyProductVariants = ShopifyProductVariant
            ::join('shopify_products', 'shopify_product_variants.shopify_product_id', '=', 'shopify_products.id')
            ->leftjoin('rex_products', 'shopify_product_variants.rex_product_id', '=', 'rex_products.id')
            ->selectRaw('shopify_product_variants.external_id, shopify_product_variants.sku, '
                . 'shopify_products.title, shopify_products.shopify_store_id, '
                . 'shopify_products.external_id as shopify_product_external_id, '
                . 'rex_products.external_id as rex_product_external_id')
            ->where('shopify_products.shopify_store_id', $shopifyStore->id)
            ->where(function($query) {
                $query->where('deleted', false)->orWhereNull('deleted');
            })->paginate(20);
        return new ShopifyVariantCollection($shopifyProductVariants);
    }
}
