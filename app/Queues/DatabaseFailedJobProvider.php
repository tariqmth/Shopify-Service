<?php


namespace App\Queues;

use Carbon\Carbon;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider as LaravelDatabaseFailedJobProvider;

class DatabaseFailedJobProvider extends LaravelDatabaseFailedJobProvider
{
    /**
     * Log a failed job into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  \Exception  $exception
     * @return int|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $failed_at = Carbon::now();

        $queueWorker = app('queue.worker');
        $sync_jobs_history_unique_id = $queueWorker->getLastFailedUniqueId();
        $queueWorker->setLastFailedUniqueId(null);

        $exception = (string) $exception;

        return $this->getTable()->insertGetId(compact(
            'connection', 'queue', 'payload', 'exception', 'failed_at', 'sync_jobs_history_unique_id'
        ));
    }
}