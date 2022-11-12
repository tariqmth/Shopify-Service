<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Client\Client;
use App\Models\Notification\RexEDSNotificationHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ProcessRexEDSNotification implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $clientExternalId;
    protected $entity;
    protected $rexSalesChannelExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        $clientExternalId,
        $entity,
        $rexSalesChannelExternalId = null
    ) {
        $this->clientExternalId = $clientExternalId;
        $this->entity = $entity;
        $this->rexSalesChannelExternalId = $rexSalesChannelExternalId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RexEDSNotificationHandler $notificationHandler)
    {
        try {
            $notificationHandler->process($this->clientExternalId, $this->entity, $this->rexSalesChannelExternalId);
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
        try {
            $client = Client::where('external_id', $this->clientExternalId)->firstOrFail();
            return $client->id;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getShopifyStoreId()
    {
        return null;
    }
}
