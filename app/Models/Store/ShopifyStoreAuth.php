<?php

namespace App\Models\Store;

class ShopifyStoreAuth
{
    public function disconnect(ShopifyStore $shopifyStore)
    {
        $revokeUrl = "https://" . $shopifyStore->subdomain . ".myshopify.com/admin/api_permissions/current.json";
        $headers = array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Content-Length: 0",
            "X-Shopify-Access-Token: " . $shopifyStore->access_token
        );
        $handler = curl_init($revokeUrl);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($handler);
        if(!curl_errno($handler)) {
            $info = curl_getinfo($handler);
            if ($info['http_code'] !== 200 && $info['http_code'] !== 401) {
                throw new \Exception('Could not disconnect app from Shopify store '
                    . $shopifyStore->subdomain . '. ' . $response);
            }
        }
        curl_close($handler);
    }
}
