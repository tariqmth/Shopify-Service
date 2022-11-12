<?php

namespace App\Models\Syncer;

use App\Models\Client\Client;

abstract class RexSyncer
{
    const DEFAULT_SECONDS_BETWEEN_ATTEMPTS = 0.5;

    protected function limitApiCalls(Client $client)
    {
        if (null === env('REX_API_DELAY')) {
            $secondsBetweenAttempts = self::DEFAULT_SECONDS_BETWEEN_ATTEMPTS;
        } else {
            $secondsBetweenAttempts = env('REX_API_DELAY');
        }

        $client->api_delay = microtime(true) + $secondsBetweenAttempts;
        $client->save();
    }
}
