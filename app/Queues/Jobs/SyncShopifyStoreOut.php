<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Store\ShopifyStore;
use App\Models\Syncer\ShopifyStoreSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncShopifyStoreOut implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $shopifyStoreId;
    protected $recurring;
    protected $entityExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        ShopifyStore $shopifyStore
    ) {
        $this->shopifyStoreId = $shopifyStore->id;
        $this->entityExternalId = $shopifyStore->subdomain;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ShopifyStoreSyncer $syncer)
    {
        try {
            $syncer->performSyncOut($this->shopifyStoreId);
        } catch (ImpossibleTaskException $e) {
            Log::error($e);
            $this->fail($e);
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
        return $this->shopifyStoreId;
    }

    public function getEntityExternalId()
    {
        return $this->entityExternalId;
    }

    public function getDirection()
    {
        return 'out';
    }

    public function getClientId()
    {
        $shopifyStore = ShopifyStore::findOrFail($this->shopifyStoreId);
        return $shopifyStore->rexSalesChannel->client->id;
    }

    public function getShopifyStoreId()
    {
        return $this->shopifyStoreId;
    }
}
