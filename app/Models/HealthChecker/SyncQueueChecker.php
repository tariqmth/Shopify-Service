<?php

namespace App\Models\HealthChecker;

use App\Queues\Jobs\TestQueue;
use Illuminate\Support\Facades\Artisan;
use PragmaRX\Health\Support\Result;
use PragmaRX\Health\Checkers\Base;
use App\Queues\Worker;
use Illuminate\Queue\WorkerOptions;

class SyncQueueChecker extends Base
{
    protected $queueWorker;

    public function __construct(
        Worker $queueWorker
    ) {
        $this->queueWorker = $queueWorker;
    }

    /**
     * Check resource.
     *
     * @return Result
     */
    public function check()
    {
        $connection = $this->target->connection ?: 'database_sync';

        $queue = 'test';

        TestQueue::dispatch()
            ->onConnection($connection)
            ->onQueue($queue);

        if ($this->getJobCount() !== 1) {
            return $this->makeResult(false);
        }

        $this->queueWorker->runNextJob($connection, $queue, $this->gatherWorkerOptions());

        return $this->makeResult($this->getJobCount() === 0);
    }

    /**
     * Gather all of the queue worker options as a single object.
     *
     * @return \Illuminate\Queue\WorkerOptions
     */
    protected function gatherWorkerOptions()
    {
        return new WorkerOptions(0, 0, 0, 0, 0, false);
    }

    protected function getJobCount()
    {
        return \DB::table('sync_jobs')->where('queue', 'test')->count();
    }
}
