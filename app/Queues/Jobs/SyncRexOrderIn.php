<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Order\RexOrder;
use App\Models\Syncer\RexOrderSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use RetailExpress\SkyLink\Sdk\Sales\Orders\Order as RexOrderData;

class SyncRexOrderIn implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $rexOrderId;
    protected $shopifyOrderData;
    protected $clientId;
    protected $entityExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        RexOrder $rexOrder,
        $shopifyOrderData
    ) {
        $this->rexOrderId = $rexOrder->id;
        $this->shopifyOrderData = $shopifyOrderData;
        $this->clientId = $rexOrder->rexSalesChannel->client_id;
        $this->entityExternalId = $rexOrder->external_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RexOrderSyncer $syncer)
    {
        try {
            $syncer->performSyncInFromShopify($this->rexOrderId, $this->shopifyOrderData);
        } catch (ImpossibleTaskException $e) {
            Log::error($e);
            $this->fail($e);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    public function getSource()
    {
        return 'rex';
    }

    public function getEntityId()
    {
        return $this->rexOrderId;
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
        return null;
    }
}
