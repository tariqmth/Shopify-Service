<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Order\ShopifyOrder;
use App\Models\Syncer\ShopifyOrderSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncShopifyOrderOut implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $shopifyOrderId;
    protected $syncer;
    protected $entityExternalId;
    protected $shopifyOrderData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        ShopifyOrder $shopifyOrder,
        $shopifyOrderData = null
    ) {
        $this->shopifyOrderId = $shopifyOrder->id;
        $this->entityExternalId = $shopifyOrder->external_id;
        $this->shopifyOrderData = $shopifyOrderData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ShopifyOrderSyncer $syncer)
    {
        try {
            $syncer->performSyncOut($this->shopifyOrderId, $this->shopifyOrderData);
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
        return $this->shopifyOrderId;
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
        $shopifyOrder = ShopifyOrder::findOrFail($this->shopifyOrderId);
        return $shopifyOrder->shopifyStore->rexSalesChannel->client->id;
    }

    public function getShopifyStoreId()
    {
        $shopifyOrder = ShopifyOrder::findOrFail($this->shopifyOrderId);
        return $shopifyOrder->shopify_store_id;
    }
}
