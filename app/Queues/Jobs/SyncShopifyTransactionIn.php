<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Fulfillment\ShopifyFulfillment;
use App\Models\Location\ShopifyFulfillmentService;
use App\Models\Payment\ShopifyTransaction;
use App\Models\Syncer\ShopifyFulfillmentSyncer;
use App\Models\Syncer\ShopifyTransactionSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\Syncer\ShopifyProductSyncer;

class SyncShopifyTransactionIn implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $shopifyTransactionId;
    protected $shopifyTransactionData;
    protected $shopifyStoreId;
    protected $clientId;
    protected $entityExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        ShopifyTransaction $shopifyTransaction,
        $shopifyTransactionData
    ) {
        $this->shopifyTransactionId = $shopifyTransaction->id;
        $this->shopifyTransactionData = $shopifyTransactionData;
        $shopifyStore = $shopifyTransaction->shopifyOrder->shopifyStore;
        $this->shopifyStoreId = $shopifyStore->id;
        $this->clientId = $shopifyStore->rexSalesChannel->client_id;
        $this->entityExternalId = $shopifyTransaction->external_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ShopifyTransactionSyncer $syncer)
    {
        try {
            $syncer->performSyncIn($this->shopifyTransactionId, $this->shopifyTransactionData);
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
        return $this->shopifyTransactionId;
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
