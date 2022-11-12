<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ConvertTime;
use App\Http\Resources\SyncJobsHistoryCollection;
use App\Models\Client\Client;
use App\Models\Job\SyncJobsHistory;
use App\Models\Job\SyncJobsRepository;
use App\Models\Job\SyncJobsStatus;
use App\Models\Job\SyncJobsStatusUpdate;
use App\Models\Store\ShopifyStore;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Http\Resources\SyncJobsHistory as SyncJobsHistoryResource;
use Illuminate\Support\Facades\DB;

class SyncJobsHistoryController extends Controller
{
    use ConvertTime;

    const FIXED_SEARCH_FIELDS = [
        'source',
        'queue',
        'direction'
    ];

    const TEXT_SEARCH_FIELDS = [
        'unique_id',
        'parent_unique_id',
        'entity_id',
        'entity_external_id',
        'client_name',
        'shopify_store_subdomain'
    ];

    protected $syncJobsRepository;

    public function __construct(
        SyncJobsRepository $syncJobsRepository
    ) {
        $this->syncJobsRepository = $syncJobsRepository;
        $this->middleware('auth');
    }

    public function getForApi($uniqueId)
    {
        return new SyncJobsHistoryResource(
            $this->syncJobsRepository->getHistory($uniqueId)
        );
    }

    public function getForWeb($uniqueId)
    {
        $histories = $this->single($uniqueId);

        $this->addLabels($histories);

        $this->addStyling($histories);

        $logs = $this->syncJobsRepository->getLogs($uniqueId)->sortByDesc('id');

        $failedJob = DB::table('failed_jobs')
            ->select(['payload'])
            ->where('sync_jobs_history_unique_id', $uniqueId)
            ->whereNotNull('sync_jobs_history_unique_id')
            ->first();

        return view('history.item', [
            'sync_job_histories' => $histories,
            'sync_job_logs' => $logs,
            'failed_job' => $failedJob
        ]);
    }

    public function allForApi(Request $request)
    {
        $histories = $this->all($request);
        $collection = new SyncJobsHistoryCollection($histories);
//      Disabled due to performance issues
//      $collection->additional(['meta' => $this->meta($request)]);
        return $collection;
    }

    public function allForWeb(Request $request)
    {
        $request->flash();

        $histories = $this->all($request);

        if ($request->input('aest')) {
            foreach ($histories as $history) {
                $history->update_created_at = $this->convertTimeToAest($history->update_created_at);
            }
        }

        $this->addLabels($histories);

        $this->addStyling($histories);

        return view('history.all', [
            'sync_job_histories' => $histories,
//          Disabled due to performance issues
//          'meta' => $this->meta($request)
        ]);
    }

    private function addStyling($histories)
    {
        foreach ($histories as $history) {
            $history->short_id = $history->unique_id ? substr($history->unique_id, 19) : null;
            $history->background = isset($history->short_id) ? '#' . $history->short_id : 'transparent';
            if (isset($history->short_id) && base_convert($history->short_id, 16, 10) < 8000000) {
                $history->color = '#FFF';
            } else {
                $history->color = '#000';
            }

            $history->parent_short_id = $history->unique_id ? substr($history->parent_unique_id, 19) : null;
            $history->parent_background = isset($history->parent_short_id) ? '#' . $history->parent_short_id : 'transparent';
            if (isset($history->parent_short_id) && base_convert($history->parent_short_id, 16, 10) < 8000000) {
                $history->parent_color = '#FFF';
            } else {
                $history->parent_color = '#000';
            }
        }
    }

    private function addLabels($histories)
    {
        foreach ($histories as $history) {
            if (key_exists($history->status_code, SyncJobsStatus::DEFAULT_LABELS)) {
                $history->status_label = SyncJobsStatus::DEFAULT_LABELS[$history->status_code];
            } else {
                $history->status_label = null;
            }
        }
    }

    private function meta(Request $request)
    {
        $completed = $this->getQuery($request)
            ->where('sync_jobs_status_updates.sync_jobs_status_code', '=', SyncJobsStatus::COMPLETE_CODE)
            ->whereDate('sync_jobs_status_updates.created_at', Carbon::today())
            ->count(DB::raw('DISTINCT unique_id'));

        $failed = $this->getQuery($request)
            ->where('sync_jobs_status_updates.sync_jobs_status_code', '=', SyncJobsStatus::FAILED_CODE)
            ->whereDate('sync_jobs_status_updates.created_at', Carbon::today())
            ->count(DB::raw('DISTINCT unique_id'));

        $startTimeHistory = $this->getQuery($request)->whereDate('sync_jobs_status_updates.created_at', Carbon::today())->first();
        $startTime = isset($startTimeHistory) ? new \DateTime($startTimeHistory->created_at) : null;
        $endTimeHistory = $this->getQuery($request)->whereDate('sync_jobs_status_updates.created_at', Carbon::today())->orderBy('sync_jobs_status_updates.id', 'desc')->first();
        $endTime = isset($endTimeHistory) ? new \DateTime($endTimeHistory->created_at) : null;
        $duration = isset($startTime) && isset($endTime) ? $endTime->getTimestamp() - $startTime->getTimestamp() : null;
        $durationFormatted = isset($startTime) && isset($endTime) ? $startTime->diff($endTime)->format('%a:%H:%I:%S') : null;
        $ended = $completed + $failed;
        $successRate = $ended ? round(($completed / $ended) * 100) : null;

        return [
            'completed' => $completed,
            'failed' => $failed,
            'success_rate' => $successRate,
            'duration' => $duration,
            'duration_formatted' => $durationFormatted
        ];
    }

    private function all(Request $request)
    {
        return $this->getQuery($request)
            ->select(DB::raw(
                'sync_jobs_history.*, '
                . 'sync_jobs_status_updates.sync_jobs_status_code as status_code, '
                . 'sync_jobs_status_updates.created_at as update_created_at'))
            ->orderBy('sync_jobs_status_updates.id', 'desc')
            ->simplePaginate(20);
    }

    private function single($uniqueId)
    {
        return $this->getSingularQuery($uniqueId)
            ->select(DB::raw(
                'sync_jobs_history.*, '
                . 'sync_jobs_status_updates.sync_jobs_status_code as status_code, '
                . 'sync_jobs_status_updates.created_at as update_created_at'))
            ->orderBy('sync_jobs_status_updates.id', 'desc')
            ->get();
    }

    private function getQuery(Request $request)
    {
        $query = SyncJobsStatusUpdate::query();
        $this->addFilters($query, $request);
        $this->addJoins($query);
        return $query;
    }

    private function getSingularQuery($uniqueId)
    {
        $query = SyncJobsStatusUpdate::query();
        $query->where('sync_jobs_history.unique_id', 'LIKE', $uniqueId);
        $query->orWhere('sync_jobs_history.parent_unique_id', 'LIKE', $uniqueId);
        $this->addJoins($query);
        return $query;
    }

    private function addJoins(Builder $query)
    {
        return $query
            ->leftJoin('sync_jobs_history', 'sync_jobs_history.unique_id', '=', 'sync_jobs_status_updates.sync_jobs_history_unique_id');
    }

    private function addFilters(Builder $query, $request)
    {
        foreach (self::FIXED_SEARCH_FIELDS as $field) {
            if (!is_null($request->input($field))) {
                $query->where('sync_jobs_history.' . $field, 'LIKE', $request->input($field));
            }
        }

        foreach (self::TEXT_SEARCH_FIELDS as $field) {
            if (!is_null($request->input($field))) {
                $query->where('sync_jobs_history.' . $field, 'LIKE', '%' . $request->input($field) . '%');
            }
        }

        if (!is_null($request->input('start_time'))) {
            $startTime = $request->input('start_time');
            if ($request->input('aest')) {
                $startTime = $this->convertTimeFromAest($startTime);
            }
            $query->where('sync_jobs_status_updates.created_at', '>=', $startTime);
        }

        if (!is_null($request->input('end_time'))) {
            $endTime = $request->input('end_time');
            if ($request->input('aest')) {
                $endTime = $this->convertTimeFromAest($endTime);
            }
            $query->where('sync_jobs_status_updates.created_at', '<=', $endTime);
        }

        if (!is_null($request->input('status'))) {
            switch($request->input('status')) {
                case 'queued':
                    $statusCode = 10;
                    break;
                case 'processing':
                    $statusCode = 20;
                    break;
                case 'failed':
                    $statusCode = 40;
                    break;
                case 'completed':
                    $statusCode = 50;
                    break;
                default:
                    $statusCode = null;
            }
            if ($statusCode !== null) {
                $query->where('sync_jobs_status_updates.sync_jobs_status_code', $statusCode);
            }
        }
    }

    private function limitToToday(Builder $query)
    {
        $query->whereDate('created_at', Carbon::today());
    }
}
