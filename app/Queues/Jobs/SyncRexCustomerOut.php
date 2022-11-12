<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Customer\RexCustomer;
use App\Models\Syncer\RexCustomerSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncRexCustomerOut implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $rexCustomerId;
    protected $clientId;
    protected $entityExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        RexCustomer $rexCustomer
    ) {
        $this->rexCustomerId = $rexCustomer->id;
        $this->clientId = $rexCustomer->rexSalesChannel->client_id;
        $this->entityExternalId = $rexCustomer->external_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RexCustomerSyncer $syncer)
    {
        try {
            $syncer->performSyncOut($this->rexCustomerId);
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
        return $this->rexCustomerId;
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
