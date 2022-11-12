<?php

namespace App\Models\Job;

use App\Queues\Jobs\SyncDatabaseJob;
use Illuminate\Support\Facades\Log;

class SyncJobsRepository
{
    const IMMUTABLE_PROPERTIES = [
        'source',
        'queue',
        'entity_id',
        'entity_external_id',
        'direction',
        'client_id',
        'shopify_store_id',
        'unique_id',
        'parent_unique_id',
        'client_name',
        'shopify_store_subdomain'
    ];

    public function getHistory($uniqueId)
    {
        return SyncJobsHistory::where('unique_id', $uniqueId)->first();
    }

    public function saveHistory($jobRecord)
    {
        $history = new SyncJobsHistory;

        foreach (self::IMMUTABLE_PROPERTIES as $immutableProperty) {
            $history->$immutableProperty = $jobRecord->$immutableProperty;
        }

        $history->save();
        return $history;
    }

    public function saveStatusUpdate($jobUniqueId, $statusCode)
    {
        $syncJobsStatusUpdate = new SyncJobsStatusUpdate;
        $syncJobsStatusUpdate->sync_jobs_history_unique_id = $jobUniqueId;
        $syncJobsStatusUpdate->sync_jobs_status_code = $statusCode;
        $syncJobsStatusUpdate->save();
    }

    public function log($jobUniqueId, $channel, $message, $level, $context)
    {
        $syncJobsLog = new SyncJobsLog;
        $syncJobsLog->sync_jobs_history_unique_id = $jobUniqueId;
        $syncJobsLog->channel = $channel;
        $syncJobsLog->message = $message;
        $syncJobsLog->level = $level;
        $syncJobsLog->context = $context;
        $syncJobsLog->save();
    }

    public function getLogs($syncJobsHistoryUniqueId)
    {
        return SyncJobsLog::where('sync_jobs_history_unique_id', $syncJobsHistoryUniqueId)->get();
    }
}