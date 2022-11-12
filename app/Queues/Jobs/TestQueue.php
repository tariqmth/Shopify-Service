<?php

namespace App\Queues\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class TestQueue implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Processed test job.');
    }

    public function getSource()
    {
        return 'rex';
    }

    public function getEntityId()
    {
        return null;
    }

    public function getEntityExternalId()
    {
        return null;
    }

    public function getDirection()
    {
        return 'out';
    }

    public function getClientId()
    {
        return null;
    }

    public function getShopifyStoreId()
    {
        return null;
    }
}
