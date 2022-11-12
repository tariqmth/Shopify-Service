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

class SyncShopifyTransactionOut implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $shopifyTransactionId;
    protected $entityExternalId;
    protected $shopifyTransactionData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        ShopifyTransaction $shopifyTransaction,
        $shopifyTransactionData = null
    ) {
        $this->shopifyTransactionId = $shopifyTransaction->id;
        $this->entityExternalId = $shopifyTransaction->external_id;
        $this->shopifyTransactionData = $shopifyTransactionData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ShopifyTransactionSyncer $syncer)
    {
        try {
            $syncer->performSyncOut($this->shopifyTransactionId, $this->shopifyTransactionData);
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
        return 'out';
    }

    public function getClientId()
    {
        $shopifyFulfillment = ShopifyTransaction::findOrFail($this->shopifyTransactionId);
        return $shopifyFulfillment->shopifyOrder->shopifyStore->rexSalesChannel->client->id;
    }

    public function getShopifyStoreId()
    {
        $shopifyFulfillment = ShopifyTransaction::findOrFail($this->shopifyTransactionId);
        return $shopifyFulfillment->shopifyOrder->shopifyStore->id;
    }
}
