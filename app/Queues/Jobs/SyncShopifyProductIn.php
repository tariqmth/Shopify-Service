<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\Product\ShopifyProduct;
use App\Models\Syncer\ShopifyProductSyncer;

class SyncShopifyProductIn implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $shopifyProductId;
    protected $data;
    protected $syncer;
    protected $shopifyStoreId;
    protected $clientId;
    protected $entityExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        ShopifyProduct $shopifyProduct,
        $productData
    ) {
        $this->shopifyProductId = $shopifyProduct->id;
        $this->data = $productData;
        $shopifyStore = $shopifyProduct->shopifyStore;
        $this->shopifyStoreId = $shopifyStore->id;
        $this->clientId = $shopifyStore->rexSalesChannel->client_id;
        $this->entityExternalId = $shopifyProduct->external_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ShopifyProductSyncer $syncer)
    {
        try {
            $syncer->performSyncIn($this->shopifyProductId, $this->data);
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
        return $this->shopifyProductId;
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
