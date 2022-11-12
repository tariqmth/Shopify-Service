<?php

namespace App\Models\Syncer;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ExternalEntityNotFoundException;
use App\Exceptions\InvalidDataException;
use App\Models\Store\ShopifyStore;
use Illuminate\Support\Facades\Log;

abstract class ShopifySyncer
{
    const API_MIN_BUCKET_SIZE = 20;
    const API_MAX_BUCKET_SIZE = 40;
    const API_DRIP_RATE = 2;

    protected function limitApiCalls(ShopifyStore $shopifyStore, $creditLeft)
    {
        if ($creditLeft < static::API_MIN_BUCKET_SIZE) {
            $apiThreshold = static::API_MAX_BUCKET_SIZE - $creditLeft;
            $apiDelay = $apiThreshold / static::API_DRIP_RATE;
            $apiDelayMicrotime = microtime(true) + $apiDelay;
            $shopifyStore->api_delay = $apiDelayMicrotime;
            $shopifyStore->save();
        } elseif ($shopifyStore->api_delay !== null) {
            $shopifyStore->api_delay = null;
            $shopifyStore->save();
        }
    }

    protected function verifyReponse($response)
    {
        $status = $response->httpStatus();

        if ($status >= 400) {
            Log::error('Invalid response returned from Shopify', [
                'http_status' => $response->httpStatus(),
                'response' => $response->parsedResponse()
            ]);
        }

        switch ($status) {
            case 200:
                return;
            case 201:
                return;
            case 401:
                throw new AuthenticationException(serialize($response->parsedResponse()));
            case 404:
                throw new ExternalEntityNotFoundException(serialize($response->parsedResponse()));
            case 422:
                throw new InvalidDataException(serialize($response->parsedResponse()));
        }

        throw new \Exception('Sync failed: ' . serialize($response->parsedResponse()));
    }

    protected function handleAuthFailure(ShopifyStore $shopifyStore)
    {
        Log::error('Authentication failed for Shopify store '
            . $shopifyStore->subdomain
            . '. Clearing credentials.');
        $shopifyStore->clearCredentials();
        $shopifyStore->enabled = false;
        $shopifyStore->save();
    }
}
