<?php

namespace App\Providers;

use App\Models\Job\SyncJobsRepository;
use App\Models\Job\SyncJobsStatus;
use App\Models\Product\RexProduct;
use App\Queues\Jobs\SyncDatabaseJob;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(SyncJobsRepository $syncJobsRepository)
    {
        $this->app['request']->server->set('HTTPS', true);

        Queue::before(function (JobProcessing $event) use ($syncJobsRepository) {
            if ($event->job instanceof SyncDatabaseJob) {
                try {
                    $queueWorker = app('queue.worker');
                    $jobHistory = $syncJobsRepository->getHistory($event->job->getJobRecord()->unique_id);
                    if (isset($jobHistory)) {
                        $syncJobsRepository->saveStatusUpdate($jobHistory->unique_id, SyncJobsStatus::PROCESSING_CODE);
                        $queueWorker->setRunningJobHistory($jobHistory);
                    }
                    $queueWorker->setLastFailedUniqueId(null);
                } catch (\Exception $e) {
                    Log::error('Could not save job processing: ' . $e);
                }
            }
        });

        Queue::failing(function (JobFailed $event) use ($syncJobsRepository) {
            if ($event->job instanceof SyncDatabaseJob) {
                try {
                    $queueWorker = app('queue.worker');
                    $currentJobHistory = $queueWorker->getRunningJobHistory();
                    if (isset($currentJobHistory)) {
                        $syncJobsRepository->saveStatusUpdate(
                            $currentJobHistory->unique_id,
                            SyncJobsStatus::FAILED_CODE
                        );
                        $queueWorker->setRunningJobHistory(null);
                        $queueWorker->setLastFailedUniqueId($currentJobHistory->unique_id);
                    } else {
                        $queueWorker->setLastFailedUniqueId(null);
                    }
                } catch (\Exception $e) {
                    Log::error('Could not save job failing: ' . $e);
                }
            }
        });

        Queue::after(function (JobProcessed $event) use ($syncJobsRepository) {
            if ($event->job instanceof SyncDatabaseJob) {
                try {
                    $queueWorker = app('queue.worker');
                    $currentJobHistory = $queueWorker->getRunningJobHistory();
                    if (isset($currentJobHistory)) {
                        $syncJobsRepository->saveStatusUpdate(
                            $currentJobHistory->unique_id,
                            SyncJobsStatus::COMPLETE_CODE
                        );
                        $queueWorker->setRunningJobHistory(null);
                    }
                } catch (\Exception $e) {
                    Log::error('Could not save job completion: ' . $e);
                }
            }
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
