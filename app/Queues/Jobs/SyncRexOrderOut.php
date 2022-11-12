<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Order\RexOrder;
use App\Models\Syncer\RexOrderSyncer;
use App\Models\Store\RexSalesChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use RetailExpress\SkyLink\Sdk\Sales\Orders\Order as RexOrderData;

class SyncRexOrderOut implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $rexOrderId;
    protected $clientId;
    protected $entityExternalId;
    protected $rexSalesChannelId;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        RexOrder $rexOrder
    ) {
        $this->rexOrderId           = $rexOrder->id;
        $this->clientId             = $rexOrder->rexSalesChannel->clientId;
        $this->entityExternalId     = $rexOrder->external_id;
        $this->rexSalesChannelId    = $rexOrder->rex_sales_channel_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RexOrderSyncer $syncer)
    {
        try {
            $syncer->performSyncOut($this->rexOrderId);
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
        return 'out';
    }

    public function getClientId()
    {
        $client_id = $this->clientId;
        if (null === $client_id)
        {

            $RexSalesChannel = RexSalesChannel::find($this->rexSalesChannelId);
            if (null !== $RexSalesChannel)
            {
                $client_id = $RexSalesChannel->client_id;
            }
        }
        return $client_id;
    }

    public function getShopifyStoreId()
    {
        return null;
    }
}
