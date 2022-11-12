<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Store\RexSalesChannel;
use App\Models\Syncer\RexOutletSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\Product\RexProduct;
use App\Models\Syncer\RexProductSyncer;

class SyncAllRexOutletsOut implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $rexSalesChannelId;
    protected $clientId;
    protected $entityExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        RexSalesChannel $rexSalesChannel
    ) {
        $this->rexSalesChannelId = $rexSalesChannel->id;
        $this->clientId = $rexSalesChannel->client->id;
        $this->entityExternalId = $rexSalesChannel->external_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RexOutletSyncer $syncer)
    {
        try {
            $syncer->performSyncAllOut($this->rexSalesChannelId);
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
        return $this->rexSalesChannelId;
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
