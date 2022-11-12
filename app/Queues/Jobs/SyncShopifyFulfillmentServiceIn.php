<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Location\ShopifyFulfillmentService;
use App\Models\Syncer\ShopifyFulfillmentServiceSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\Syncer\ShopifyProductSyncer;

class SyncShopifyFulfillmentServiceIn implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $shopifyFulfillmentServiceId;
    protected $shopifyStoreId;
    protected $clientId;
    protected $entityExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        ShopifyFulfillmentService $shopifyFulfillmentService
    ) {
        $this->shopifyFulfillmentServiceId = $shopifyFulfillmentService->id;
        $shopifyStore = $shopifyFulfillmentService->shopifyStore;
        $this->shopifyStoreId = $shopifyStore->id;
        $this->clientId = $shopifyStore->rexSalesChannel->client_id;
        $this->entityExternalId = $shopifyFulfillmentService->external_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ShopifyFulfillmentServiceSyncer $syncer)
    {
        try {
            $syncer->performSyncIn($this->shopifyFulfillmentServiceId);
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
        return $this->shopifyFulfillmentServiceId;
    }

    public function getEntityExternalId()
    {
        return $this->entityExternalId;
    }

    public function getDirection()
    {
        return 'in';
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getShopifyStoreId()
    {
        return $this->shopifyStoreId;
    }
}
