<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;

class SyncJobsStatusUpdate extends Model
{
    public $timestamps = false;

    public function syncJobsStatus()
    {
        return $this->belongsTo('App\Models\Job\SyncJobsStatus', 'sync_jobs_status_code', 'code');
    }

    public function syncJobsHistory()
    {
        return $this->belongsTo('App\Models\Job\SyncJobsHistory','sync_jobs_history_unique_id', 'unique_id');
    }
}