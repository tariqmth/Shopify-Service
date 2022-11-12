<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\Product\RexProduct;
use App\Models\Syncer\RexProductSyncer;

class SyncRexProductInventoryOut implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $rexProductId;
    protected $syncer;
    protected $clientId;
    protected $entityExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        RexProduct $rexProduct
    ) {
        $this->rexProductId = $rexProduct->id;
        $this->clientId = $rexProduct->rexSalesChannel->client_id;
        $this->entityExternalId = $rexProduct->external_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RexProductSyncer $syncer)
    {
        try {
            $syncer->performSyncOutInventory($this->rexProductId);
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
        return $this->rexProductId;
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
        return null;
    }
}
