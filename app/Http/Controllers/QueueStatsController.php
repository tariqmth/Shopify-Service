<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ConvertTime;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueStatsController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    public function allForWeb()
    {
        $sync_jobs = DB::table('sync_jobs')
            ->select('client_id', 'shopify_store_id', 'queue', 'direction', 'source', 'attempts');

        $plus_inventory_jobs = DB::table('sync_inventory_jobs')
            ->select('client_id', 'shopify_store_id', 'queue', 'direction', 'source', 'attempts')
            ->unionAll($sync_jobs);

        $stats = DB::query()->fromSub($plus_inventory_jobs, 'all_jobs')
            ->select('clients.name as client', 'shopify_stores.subdomain as store', 'all_jobs.queue', 'all_jobs.direction', 'all_jobs.source', 'all_jobs.attempts', DB::raw('count(*) as jobs'))
            ->leftJoin('clients','all_jobs.client_id','=','clients.id')
            ->leftJoin('shopify_stores','all_jobs.shopify_store_id','=','shopify_stores.id')
            ->groupBy('all_jobs.client_id', 'all_jobs.shopify_store_id', 'all_jobs.queue', 'all_jobs.direction', 'all_jobs.source', 'all_jobs.attempts')
            ->orderBy('all_jobs.attempts', 'asc')
            ->orderBy('jobs', 'desc')
            ->get();

        return view('queue.all', [ 'sync_job_queue' => $stats ]);
    }
}
