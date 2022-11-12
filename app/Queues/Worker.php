<?php

namespace App\Queues;

use Illuminate\Queue\Worker as LaravelWorker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Log;

class Worker extends LaravelWorker
{
    const MAX_TIME = 300;

    protected $startTime;
    protected $jobHistory;
    protected $lastFailedUniqueId;

    /**
     * Stop the process if necessary.
     *
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @param  int  $lastRestart
     */
    protected function stopIfNecessary(WorkerOptions $options, $lastRestart, $job = null)
    {
        if ($this->shouldQuit) {
            $this->stop();
        } elseif ($this->memoryExceeded($options->memory) || $this->timeExceeded()) {
            $this->stop(12);
        } elseif ($this->queueShouldRestart($lastRestart)) {
            $this->stop();
        } elseif ($options->stopWhenEmpty && is_null($job)) {
            $this->stop();
        }
    }

    /**
     * Register the worker timeout handler.
     *
     * Logging has been included here. Note that ext-pcntl is required but cannot be installed on Windows systems.
     *
     * @param  \Illuminate\Contracts\Queue\Job|null  $job
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    protected function registerTimeoutHandler($job, WorkerOptions $options)
    {
        // We will register a signal handler for the alarm signal so that we can kill this
        // process if it is running too long because it has frozen. This uses the async
        // signals supported in recent versions of PHP to accomplish it conveniently.
        pcntl_signal(SIGALRM, function () {
            Log::alert('The process has timed out and will be terminated.');
            $this->kill(1);
        });

        pcntl_alarm(
            max($this->timeoutForJob($job, $options), 0)
        );
    }

    public function timeExceeded()
    {
        if ($this->startTime) {
            return microtime(true) > $this->startTime + self::MAX_TIME;
        } else {
            $this->startTime = microtime(true);
            return false;
        }
    }

    public function getRunningJobHistory()
    {
        return $this->jobHistory;
    }

    public function setRunningJobHistory($job)
    {
        $this->jobHistory = $job;
    }

    public function getLastFailedUniqueId()
    {
        return $this->lastFailedUniqueId;
    }

    public function setLastFailedUniqueId($uniqueId)
    {
        $this->lastFailedUniqueId = $uniqueId;
    }
}