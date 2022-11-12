<?php

namespace App\Packages;

use Shopify\ShopifyClient as ShopifySdk;
use App\Models\Store\ShopifyStore;

class ShopifySdkFactory
{
    public function getSdk(ShopifyStore $store, $requirePrivateCredentials = false)
    {
        $shopifySdk = app()->make(ShopifySdk::class);
        $shopifySdk->setShopName($store->subdomain . '.myshopify.com');
        $shopifySdk->setApiVersion(env('SHOPIFY_API_VERSION'));

        try { if ($requirePrivateCredentials) {
                // if api_key = anything else, use private app
                if (isset($store->api_key) && isset($store->password) 
                    && $store->api_key !== 'giftvouchers_enabled') 
                {
                    $shopifySdk->setPrivateCredentials($store->api_key, $store->password);                
                }
                
                //if api_key = "giftvouchers_enabled", use main app                
                elseif(isset($store->api_key) && $store->api_key ==='giftvouchers_enabled' 
                    && isset($store->access_token) )
                {
                    $shopifySdk->setAccessToken($store->access_token);
                }
                
                //if api_key = blank, use main app
                elseif(empty($store->api_key) && isset($store->access_token) )
                {
                    $shopifySdk->setAccessToken($store->access_token);
                
                } else {
                    throw new \Exception('Private credentials required but none set.');
                }
            } elseif (isset($store->access_token)) {
                $shopifySdk->setAccessToken($store->access_token);
            } elseif (isset($store->api_key) && isset($store->password)) {
                $shopifySdk->setPrivateCredentials($store->api_key, $store->password);                
            } else {
                throw new \Exception('Could not find any credentials.');
            }
        } catch (\Exception $e) {
            throw new \Exception('Invalid access details for Shopify store ' . $store->subdomain . ': ' . $e);
        }

        return $shopifySdk;
    }
}