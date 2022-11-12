<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Store\ShopifyStore;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncShopifyMock implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    const DEFAULT_SECONDS_BETWEEN_ATTEMPTS = 0.5;
    const SLEEP = 0.5;

    protected $shopifyStoreId;
    protected $withDatabaseLimiting;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        ShopifyStore $shopifyStore,
        $withDatabaseLimiting = true
    ) {
        $this->shopifyStoreId = $shopifyStore->id;
        $this->withDatabaseLimiting = $withDatabaseLimiting;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $shopifyStore = ShopifyStore::find($this->shopifyStoreId);
            if ($this->withDatabaseLimiting) {
                $this->limitApiCalls($shopifyStore);
            }
            sleep(self::SLEEP);
            Log::info('Processed shopify test job.');
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    public function getSource()
    {
        return 'shopify';
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
        return $this->shopifyStoreId;
    }

    private function limitApiCalls(ShopifyStore $shopifyStore)
    {
        $shopifyStore->api_delay = microtime(true) + self::DEFAULT_SECONDS_BETWEEN_ATTEMPTS;
        $shopifyStore->save();
    }
}
