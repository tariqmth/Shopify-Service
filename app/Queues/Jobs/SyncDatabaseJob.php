<?php

namespace App\Queues\Jobs;

use Illuminate\Container\Container;
use App\Queues\SyncQueue;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\DatabaseJob;

class SyncDatabaseJob extends DatabaseJob implements JobContract
{
    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \App\Queues\SyncQueue  $database
     * @param  \stdClass  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, SyncQueue $database, $job, $connectionName, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->database = $database;
        $this->container = $container;
        $this->connectionName = $connectionName;
    }

    public function getJobRecord()
    {
        return $this->job;
    }

    public function getDatabase()
    {
        return $this->database;
    }
}
