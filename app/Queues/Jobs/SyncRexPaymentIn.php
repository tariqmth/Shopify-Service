<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Payment\RexPayment;
use App\Models\Syncer\RexPaymentSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncRexPaymentIn implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $rexPaymentId;
    protected $shopifyTransactionData;
    protected $clientId;
    protected $entityExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        RexPayment $rexPayment,
        $shopifyTransactionData
    ) {
        $this->rexPaymentId = $rexPayment->id;
        $this->shopifyTransactionData = $shopifyTransactionData;
        $this->clientId = $rexPayment->rexOrder->rexSalesChannel->client_id;
        $this->entityExternalId = $rexPayment->external_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RexPaymentSyncer $syncer)
    {
        try {
            $syncer->performSyncInFromShopify($this->rexPaymentId, $this->shopifyTransactionData);
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
        return $this->rexPaymentId;
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
