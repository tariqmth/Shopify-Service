<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Attribute\RexAttributeOption;
use App\Models\Syncer\RexAttributeOptionSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncRexAttributeOptionOut implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $attributeOptionId;
    protected $syncer;
    protected $clientId;
    protected $entityExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        RexAttributeOption $attributeOption
    ) {
        $this->attributeOptionId = $attributeOption->id;
        $this->clientId = $attributeOption->rexAttribute->client_id;
        $this->entityExternalId = $attributeOption->option_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RexAttributeOptionSyncer $syncer)
    {
        try {
            $syncer->performSyncOut($this->attributeOptionId);
        } catch (ImpossibleTaskException $e) {
            Log::error($e);
            $this->fail();
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
        return $this->attributeOptionId;
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
