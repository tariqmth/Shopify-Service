<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Client\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncRexMock implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    const DEFAULT_SECONDS_BETWEEN_ATTEMPTS = 0.5;
    const SLEEP = 1;

    protected $clientId;
    protected $withDatabaseLimiting;
    protected $followWithShopify;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        Client $client,
        $withDatabaseLimiting = true,
        $followWithShopify = true
    ) {
        $this->clientId = $client->id;
        $this->withDatabaseLimiting = $withDatabaseLimiting;
        $this->followWithShopify = $followWithShopify;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $client = Client::find($this->clientId);
            if ($this->withDatabaseLimiting) {
                $this->limitApiCalls($client);
            }
            sleep(self::SLEEP);
            if ($this->followWithShopify) {
                $this->mockSyncToShopify($client);
            }
            Log::info('Processed rex test job.');
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
        return $this->clientId;
    }

    public function getShopifyStoreId()
    {
        return null;
    }

    private function limitApiCalls(Client $client)
    {
        if (null === env('REX_API_DELAY')) {
            $secondsBetweenAttempts = self::DEFAULT_SECONDS_BETWEEN_ATTEMPTS;
        } else {
            $secondsBetweenAttempts = env('REX_API_DELAY');
        }

        $client->api_delay = microtime(true) + $secondsBetweenAttempts;
        $client->save();
    }

    private function mockSyncToShopify(Client $client)
    {
        foreach($client->rexSalesChannels as $rexSalesChannel) {
            $shopifyStore = $rexSalesChannel->shopifyStore;
            SyncShopifyMock::dispatch($shopifyStore)
                ->onConnection('database_sync')
                ->onQueue($this->job->getQueue());
        }
    }
}
