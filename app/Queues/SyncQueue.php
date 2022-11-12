<?php

namespace App\Queues;

use App\Models\Client\Client;
use App\Models\Job\SyncJobsStatus;
use App\Models\Product\RexProduct;
use Illuminate\Database\DetectsDeadlocks;
use Illuminate\Support\Carbon;
use Illuminate\Database\Connection;
use App\Queues\Jobs\SyncDatabaseJob;
use Illuminate\Queue\Jobs\DatabaseJobRecord;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use App\Models\Store\ShopifyStore;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;

class SyncQueue extends Queue implements QueueContract
{
    use DetectsDeadlocks;

    const WORKER_BURST_LIMIT = 2;

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * The database table that holds the jobs.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected $default;

    /**
     * The expiration time of a job.
     *
     * @var int|null
     */
    protected $retryAfter = 60;

    /**
     * Job fetch strategy.
     *
     * @var int
     */
    public $windowStrategy = 1;
    /**
     * Number of parallel workers / size of the window.
     *
     * @var int
     */
    public $numWorkers = 1;

    /**
     * Create a new database queue instance.
     *
     * @param  \Illuminate\Database\Connection  $database
     * @param  string  $table
     * @param  string  $default
     * @param  int  $retryAfter
     * @return void
     */
    public function __construct(Connection $database, $table, $default = 'default', $retryAfter = 60)
    {
        $this->table = $table;
        $this->default = $default;
        $this->database = $database;
        $this->retryAfter = $retryAfter;
        $this->numWorkers = env('NUM_WORKERS') ?? 1;
        $this->windowStrategy = env('WINDOW_STRATEGY') ?? 1;
        $this->database->getPdo()->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
    }

    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return $this->database->table($this->table)
                    ->where('queue', $this->getQueue($queue))
                    ->count();
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        $jobRecord = $this->pushToDatabase(
            $queue,
            $this->createPayload($job, $data),
            0,
            0,
            $job->getSource(),
            $job->getEntityId(),
            $job->getDirection(),
            $job->getClientId(),
            $job->getShopifyStoreId(),
            $job->getEntityExternalId()
        );

        return $jobRecord;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->pushToDatabase($queue, $payload);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->pushToDatabase(
            $queue,
            $this->createPayload($job, $data),
            $delay,
            0,
            $job->getSource(),
            $job->getEntityId(),
            $job->getDirection(),
            $job->getClientId(),
            $job->getShopifyStoreId(),
            $job->getEntityExternalId()
        );
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array   $jobs
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        $queue = $this->getQueue($queue);

        $availableAt = $this->availableAt();

        return $this->database->table($this->table)->insert(collect((array) $jobs)->map(
            function ($job) use ($queue, $data, $availableAt) {
                return $this->buildDatabaseRecord(
                    $queue,
                    $this->createPayload($job, $data),
                    $availableAt,
                    0,
                    $job->getSource(),
                    $job->getEntityId(),
                    $job->getDirection(),
                    $job->getClientId(),
                    $job->getShopifyStoreId(),
                    $job->getEntityExternalId()
                );
            }
        )->all());
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\DatabaseJobRecord  $job
     * @param  int  $delay
     * @return mixed
     */
    public function release($queue, $job, $delay)
    {
        return $this->pushToDatabase(
            $queue,
            $job->payload,
            $delay,
            $job->attempts,
            $job->source,
            $job->entity_id,
            $job->direction,
            $job->client_id,
            $job->shopify_store_id,
            $job->entity_external_id
        );
    }

    /**
     * Push a raw payload to the database with a given delay.
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  int  $attempts
     * @return mixed
     */
    protected function pushToDatabase(
        $queue,
        $payload,
        $delay = 0,
        $attempts = 0,
        $source,
        $entityId,
        $direction,
        $clientId,
        $shopifyStoreId,
        $entityExternalId
    ) {
        $databaseRecord = $this->buildDatabaseRecord(
            $this->getQueue($queue),
            $payload,
            $this->availableAt($delay),
            $attempts,
            $source,
            $entityId,
            $direction,
            $clientId,
            $shopifyStoreId,
            $entityExternalId
        );

        $jobId = $this->database->table($this->table)->insertGetId($databaseRecord);

        try {
            $recordObject = (object) $databaseRecord;
            $client = Client::find($clientId);
            if (isset($client)) {
                $recordObject->client_name = $client->name;
            } else {
                $recordObject->client_name = null;
            }
            $shopifyStore = ShopifyStore::find($shopifyStoreId);
            if (isset($shopifyStore)) {
                $recordObject->shopify_store_subdomain = $shopifyStore->subdomain;
            } else {
                $recordObject->shopify_store_subdomain = null;
            }
            $syncJobsRepository = resolve('App\Models\Job\SyncJobsRepository');
            $syncJobsRepository->saveHistory($recordObject);
            $syncJobsRepository->saveStatusUpdate($recordObject->unique_id, SyncJobsStatus::QUEUED_CODE);
        } catch (\Exception $e) {
            Log::error('Could not save job queueing: ' . $e);
        }

        return $jobId;
    }

    /**
     * Create an array to insert for the given job.
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  int  $availableAt
     * @param  int  $attempts
     * @return array
     */
    protected function buildDatabaseRecord(
        $queue,
        $payload,
        $availableAt,
        $attempts = 0,
        $source,
        $entityId,
        $direction,
        $clientId,
        $shopifyStoreId,
        $entityExternalId
    ){
        $queueWorker = app('queue.worker');
        $parentHistory = $queueWorker->getRunningJobHistory();
        if (isset($parentHistory)) {
            $parentUniqueId = $parentHistory->unique_id;
        } else {
            $parentUniqueId = null;
        }

        $randomBytes = random_bytes(6);
        $randomString = bin2hex($randomBytes);
        $uniqueId = uniqid($randomString);

        return [
            'queue' => $queue,
            'attempts' => $attempts,
            'reserved_at' => null,
            'available_at' => $availableAt,
            'created_at' => $this->currentTime(),
            'payload' => $payload,
            'source' => $source,
            'entity_id'  => $entityId,
            'direction' => $direction,
            'client_id' => $clientId,
            'shopify_store_id' => $shopifyStoreId,
            'parent_unique_id' => $parentUniqueId,
            'entity_external_id' => $entityExternalId,
            'unique_id' => $uniqueId,
            'version' => 0
        ];
    }

    /**
     * Picks job from the candidates for the processing.
     *
     * @param Collection $jobs
     *
     * @return mixed
     */
    protected function pickJob($jobs)
    {
        $cnt = $jobs->count();
        if ($cnt == 1 || $this->windowStrategy == 0) {
            return $jobs[0];
        }
        // Low 2 bits encode the probabilistic
        // method for choosing one job out of N
        if (($this->windowStrategy & 3) == 1) {
            // Uniform pick
            return $jobs[mt_rand(0, $cnt - 1)];
        } elseif (($this->windowStrategy & 3) == 2) {
            // Exp. pick
            for ($i = 0; $i < $cnt; $i++) {
                if (mt_rand(0, 2) == 0) {
                    return $jobs[$i];
                }
            }
            //return $jobs[$cnt-1];
            return $jobs[0];
        }
        return $jobs[0];
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     *
     * @throws \Exception|\Throwable
     *
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        // Pops one job of the queue or return null if there is no job to process.
        //
        // In order to preserve job ordering we have to pick the first available job.
        // Workers compete for the first available job in the queue.
        //
        // Load the first available job and try to claim it.
        // During the competition it may happen another worker claims the job before we do
        // which can be easily handled and detected with optimistic locking.
        //
        // In that case we try to load another job
        // because there are apparently some more jobs in the database and pop() is supposed
        // to return such job if there is one or return null if there are no jobs so worker
        // can sleep(). Thus we have to attempt to claim jobs until there are some.
        $queue = $this->getQueue($queue);
        $job = null;

        // Get candidate job
        $job = $this->getNextAvailableJob($queue);

        // If candidate job returned
        if ( !is_null($job) ) {

            // Try to claim it
            $jobClaimed = $this->marshalJob($queue, $job);

            if (!empty($jobClaimed)) {

                // job was successfully claimed, return it.
                return $jobClaimed;

            }

        }
    }

    /**
     * Get the next available job for the queue (NOT USED)
     *
     * @param string|null $queue
     * @param int         $limit
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getNextAvailableJobs($queue, $unavailableShopifyStores, $unavailableClients, $limit = 1)
    {
        $isForShopify = rand(0, 1);

        $query = $this->database->table($this->table)
                ->where('queue', $this->getQueue($queue));

        // Step 1 get random store from queue
        if ($isForShopify) {
            $query->where('source', 'shopify')
                  ->whereNotIn('shopify_store_id', $unavailableShopifyStores);
            $shopify_store = $query->groupBy('shopify_store_id')->inRandomOrder()->pluck('shopify_store_id')->first();
        }else{
            $query->where('source', 'rex')
                  ->whereNotIn('client_id', $unavailableClients);      
            $rex_client = $query->groupBy('client_id')->inRandomOrder()->pluck('client_id')->first();
        }

        // Step 2 retrieves the oldest job from the queue of store/client randomly selected
        $query = $this->database->table($this->table);

        if ($isForShopify) {
            $query->where('source', 'shopify')
                  ->where('shopify_store_id', $shopify_store);
        } else {
            $query->where('source', 'rex')
                  ->where('client_id', $rex_client);
        }

        $query->where(function ($query) {
            $this->isAvailable($query);
            $this->isReservedButExpired($query);
        });            

        $jobs = $query->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        return $jobs;
    }

    /**
     * Get the next available job for the queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Queue\Jobs\DatabaseJobRecord|null
     */
    protected function getNextAvailableJob($queue)
    {
        $isForShopify = rand(0, 1);

        $query = $this->database->table($this->table);

        // Step 1 get random store from queue
        if ($isForShopify) {

            // Only need source and queue for non-inventory jobs
            if($this->getQueue($queue)<>'product_inventory') {

                $query->where('source', 'shopify')
                      ->where('queue', $this->getQueue($queue));

            }

            // Get candidate store by selecting at random 
            $shopify_store = $query->groupBy('shopify_store_id')->inRandomOrder()->pluck('shopify_store_id')->first();

            // Get stores that are currently unavailable
            $unavailableShopifyStores = $this->getUnavailableShopifyStores();

            // Exit if selected store is unavailable
            if ( in_array($shopify_store, $unavailableShopifyStores) )
                return null;

        } else {

            // Get candidate client by selecting at random 
            $rex_client = $query->where('source', 'rex')
                                ->where('queue', $this->getQueue($queue))
                                ->groupBy('client_id')->inRandomOrder()->pluck('client_id')->first();

            // Get clients that are currently unavailable
            $unavailableClients = $this->getUnavailableClients();

            // Exit if selected client is unavailable
            if ( in_array($rex_client, $unavailableClients) )
                return null;

        }        
        
        // Step 2 retrieves the oldest job from the queue of store/client randomly selected
        $query = $this->database->table($this->table)
            ->sharedLock();

        if ($isForShopify) {

            // Only need source and queue for non-inventory jobs
            if($this->getQueue($queue)<>'product_inventory') {

                $query->where('source', 'shopify')
                      ->where('queue', $this->getQueue($queue));

            }

            $query->where('shopify_store_id', $shopify_store);

        } else {

            $query->where('source', 'rex')
                  ->where('queue', $this->getQueue($queue))
                  ->where('client_id', $rex_client);

        }

        // Check if candidate job has been claimed
        $query->where(function ($query) {
            $this->isAvailable($query);
            $this->isReservedButExpired($query);
        });

        // Return oldest job
        $job = $query
            ->orderBy('id', 'asc')
            ->first();

        return $job ? new DatabaseJobRecord((object) $job) : null;
    }

    protected function getUnavailableShopifyStores()
    {
        $storesAtWorkerLimit = $this->database->table($this->table)
            ->select(DB::raw('COUNT(id), shopify_store_id'))
            ->whereNotNull('shopify_store_id')
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '>=', time() - 1)
            ->where('reserved_at', '<=', time())
            ->groupBy('shopify_store_id')
            ->having('COUNT(id)', '>=', self::WORKER_BURST_LIMIT)
            ->pluck('shopify_store_id')
            ->toArray();
        $unavailableStores = ShopifyStore
            ::where('enabled', true)
            ->where('api_delay', '>', microtime(true))
            ->pluck('id')
            ->toArray();
        return array_unique(array_merge($storesAtWorkerLimit, $unavailableStores));
    }

    protected function getUnavailableClients()
    {
        $clientsAtWorkerLimit = $this->database->table($this->table)
            ->select(DB::raw('COUNT(id), client_id'))
            ->whereNotNull('client_id')
            ->whereNotNull('reserved_at')
            ->where('source', '=', 'rex')
            ->where('reserved_at', '>=', time() - 1)
            ->where('reserved_at', '<=', time())
            ->groupBy('client_id')
            ->having('COUNT(id)', '>=', self::WORKER_BURST_LIMIT)
            ->pluck('client_id')
            ->toArray();
        $unavailableClients = Client
            ::where('api_delay', '>', microtime(true))
            ->pluck('id')
            ->toArray();
        return array_unique(array_merge($clientsAtWorkerLimit, $unavailableClients));
    }

    protected function delaySiblingProducts($job)
    {
        if (isset($job) && $job->source === 'rex' && $job->queue === 'product') {
            $rexProduct = RexProduct::find($job->entity_id);
            if (isset($rexProduct) && isset($rexProduct->rexProductGroup)) {
                $rexProductGroup = $rexProduct->rexProductGroup;
                foreach ($rexProductGroup->rexProducts as $siblingProduct) {
                    $this->database->table($this->table)
                        ->whereNull('reserved_at')
                        ->where('source', 'rex')
                        ->where('queue', 'product')
                        ->where('direction', $job->direction)
                        ->where('entity_id', $siblingProduct->id)
                        ->update([
                            'available_at' => $this->availableAt(20)
                        ]);
                }
            }
        }
    }

    /**
     * Modify the query to check for available jobs.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    protected function isAvailable($query)
    {
        $query->where(function ($query) {
            $query->whereNull('reserved_at')
                  ->where('available_at', '<=', $this->currentTime());
        });
    }

    /**
     * Modify the query to check for jobs that are reserved but have expired.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    protected function isReservedButExpired($query)
    {
        $expiration = Carbon::now()->subSeconds($this->retryAfter)->getTimestamp();

        $query->orWhere(function ($query) use ($expiration) {
            $query->where('reserved_at', '<=', $expiration);
        });
    }

    /**
     * Marshal the reserved job into a DatabaseJob instance.
     *
     * @param string                                   $queue
     * @param \Illuminate\Queue\Jobs\DatabaseJobRecord $job
     *
     * @return \Illuminate\Queue\Jobs\DatabaseJob
     */
    protected function marshalJob($queue, $job)
    {
        $job = $this->markJobAsReserved($job);
        if (empty($job)) {
            return;
        }
        return new SyncDatabaseJob(
            $this->container, $this, $job, $this->connectionName, $queue
        );
    }

    /**
     * Marshal the reserved job into a DatabaseJob instance.
     *
     * @param \Illuminate\Queue\Jobs\DatabaseJobRecord $job
     *
     * @return DatabaseJobRecord|null
     */
    protected function markJobAsReserved($job)
    {
        //check race condition, check if job is reserved in last 2 seconds 
       if ($job->reserved_at && (time()-$job->reserved_at < 31))
       {
            return null;
       }

        $affected = $this->database->table($this->table)
            ->where('id', $job->id)
            ->where('version', $job->version)
            ->update([
                'reserved_at' => $job->touch(),
                'attempts'    => $job->increment(),
                'version'     => new Expression('version + 1'),
            ]);
        return $affected ? $job : null;
    }

    /**
     * Delete a reserved job from the queue.
     * https://github.com/laravel/framework/issues/7046.
     *
     * @param string $queue
     * @param string $id
     *
     * @throws \Exception|\Throwable
     *
     * @return void
     */
    public function deleteReserved($queue, $id)
    {
        $this->database->table($this->table)
            ->where('id', $id)
            ->delete();
    }

    public function deleteJobsForEntities($source, $queue, $entityIds, $direction)
    {
        $this->database->table($this->table)
            ->whereNull('reserved_at')
            ->where('source', $source)
            ->where('queue', $queue)
            ->where('direction', $direction)
            ->whereIn('entity_id', $entityIds)
            ->delete();
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    public function getQueue($queue)
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the underlying database instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getDatabase()
    {
        return $this->database;
    }
}
