<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Store\ShopifyStore;
use App\Models\Syncer\ShopifyWebhookSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncShopifyWebhooksOut implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $recurring;
    protected $shopifyStoreId;
    protected $clientId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        ShopifyStore $shopifyStore,
        $recurring
    ) {
        $this->shopifyStoreId = $shopifyStore->id;
        $this->recurring = $recurring;
        $this->clientId = $shopifyStore->rexSalesChannel->client_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ShopifyWebhookSyncer $syncer)
    {
        try {
            $syncer->performSyncOut($this->shopifyStoreId);
            if ($this->recurring) {
                $this->dispatch($this->shopifyStoreId, true)
                    ->onConnection('database_sync')
                    ->onQueue('notification_service')
                    ->delay(now()->addDay());
            }
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
        return $this->clientId;
    }

    public function getShopifyStoreId()
    {
        return $this->shopifyStoreId;
    }
}
