<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Customer\ShopifyCustomer;
use App\Models\Syncer\ShopifyCustomerSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\Product\ShopifyProduct;
use App\Models\Syncer\ShopifyProductSyncer;

class SyncShopifyCustomerExternalIdOut implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $shopifyCustomerId;
    protected $data;
    protected $syncer;
    protected $clientId;
    protected $shopifyStoreId;
    protected $entityExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        ShopifyCustomer $shopifyCustomer
    ) {
        $this->shopifyCustomerId = $shopifyCustomer->id;
        $shopifyStore = $shopifyCustomer->shopifyStore;
        $this->shopifyStoreId = $shopifyStore->id;
        $this->clientId = $shopifyStore->rexSalesChannel->client_id;
        $this->entityExternalId = $shopifyCustomer->external_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ShopifyCustomerSyncer $syncer)
    {
        try {
            $syncer->performSyncOutExternalId($this->shopifyCustomerId);
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
        return $this->shopifyCustomerId;
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
        return $this->clientId;
    }

    public function getShopifyStoreId()
    {
        return $this->shopifyStoreId;
    }
}
