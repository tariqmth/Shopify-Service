<?php

namespace App\Packages;

use App\Models\Client\Client;
use RetailExpress\SkyLink\Sdk\Apis\V2\Api;
use RetailExpress\SkyLink\Sdk\Apis\V2\InvalidClientIdMiddleware;
use App\Logging\SkylinkLoggingMiddleware;
use ValueObjects\Web\Url;
use ValueObjects\Identity\UUID as Uuid;
use ValueObjects\StringLiteral\StringLiteral;

class SkylinkSdkFactory
{
    public function __construct(SkylinkLoggingMiddleware $skylinkLoggingMiddleware)
    {
        $this->skylinkLoggingMiddleware = $skylinkLoggingMiddleware;
    }

    public function getApi(Client $client)
    {
        try {
            $api = new Api(
                Url::fromNative(env('REX_URL')),
                Uuid::fromNative($client->external_id),
                StringLiteral::fromNative($client->username),
                StringLiteral::fromNative($client->password)
            );

            $api->addMiddlewareAfter(
                $this->skylinkLoggingMiddleware,
                InvalidClientIdMiddleware::class
            );
        } catch (\Exception $e) {
            throw new \Exception('Could not create SDK for Rex Client. ' . $e);
        }

        return $api;
    }
}