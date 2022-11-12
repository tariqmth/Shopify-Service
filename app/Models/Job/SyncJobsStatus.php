<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;

class SyncJobsStatus extends Model
{
    const QUEUED_CODE = 10;
    const PROCESSING_CODE = 20;
    const FAILED_CODE = 40;
    const COMPLETE_CODE = 50;
    const DEFAULT_LABELS = [
        10 => 'queued',
        20 => 'processing',
        40 => 'failed',
        50 => 'complete'
    ];

    public $timestamps = false;

    public function syncJobsUpdates()
    {
        return $this->hasMany('App\Models\Job\SyncJobsStatusUpdate', 'sync_jobs_status_code', 'code');
    }
}