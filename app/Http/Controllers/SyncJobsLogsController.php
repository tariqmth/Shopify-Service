<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ConvertTime;
use App\Models\Job\SyncJobsLog;
use Illuminate\Http\Request;

class SyncJobsLogsController extends Controller
{
    use ConvertTime;

    protected $syncJobsRepository;

    public function __construct(
        //
    ) {
        $this->middleware('auth');
    }

    public function allForWeb(Request $request)
    {
        $request->flash();

        $query = SyncJobsLog::query();

        if (!is_null($request->input('id'))) {
            $query->where('id', '=', $request->input('id'));
        }

        if (!is_null($request->input('sync_jobs_history_unique_id'))) {
            $query->where('sync_jobs_history_unique_id', 'LIKE', '%' . $request->input('sync_jobs_history_unique_id') . '%');
        }

        if (!is_null($request->input('message'))) {
            $query->where('message', 'LIKE', '%' . $request->input('message') . '%');
        }

        if (!is_null($request->input('level'))) {
            $acceptableLevels = [];
            if ($request->input('level') === 'debug') {
                $acceptableLevels = ['DEBUG','INFO','NOTICE','WARNING','ERROR','CRITICAL','ALERT','EMERGENCY'];
            } elseif ($request->input('level') === 'warning') {
                $acceptableLevels = ['WARNING','ERROR','CRITICAL','ALERT','EMERGENCY'];
            } elseif ($request->input('level') === 'error') {
                $acceptableLevels = ['ERROR','CRITICAL','ALERT','EMERGENCY'];
            }
            $query->whereIn('level', $acceptableLevels);
        }

        if (!is_null($request->input('context'))) {
            $query->where('context', 'LIKE', '%' . $request->input('context') . '%');
        }

        if (!is_null($request->input('start_time'))) {
            $startTime = $request->input('start_time');
            if ($request->input('aest')) {
                $startTime = $this->convertTimeFromAest($startTime);
            }
            $query->where('created_at', '>=', $startTime);
        }

        if (!is_null($request->input('end_time'))) {
            $endTime = $request->input('end_time');
            if ($request->input('aest')) {
                $endTime = $this->convertTimeFromAest($endTime);
            }
            $query->where('created_at', '<=', $endTime);
        }

        $logs = $query->orderBy('id', 'desc')->simplePaginate(20);

        if ($request->input('aest')) {
            foreach ($logs as $log) {
                $log->created_at = $this->convertTimeToAest($log->created_at);
            }
        }

        $this->addStyling($logs);

        return view('logs.all', [
            'logs' => $logs
        ]);
    }

    private function addStyling($logs)
    {
        foreach ($logs as $log) {
            $shortId = $log->sync_jobs_history_unique_id ? substr($log->sync_jobs_history_unique_id, 19) : null;
            $log->background = isset($shortId) ? '#' . $shortId : 'transparent';
            if (isset($shortId) && base_convert($shortId, 16, 10) < 8000000) {
                $log->color = '#FFF';
            } else {
                $log->color = '#000';
            }
        }
    }
}
