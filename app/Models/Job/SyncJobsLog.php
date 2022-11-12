<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;

class SyncJobsLog extends Model
{
    const UPDATED_AT = null;

    public function syncJobsHistory()
    {
        return $this->belongsTo('App\Models\Job\SyncJobsHistory');
    }
}