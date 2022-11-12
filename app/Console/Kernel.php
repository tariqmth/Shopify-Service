<?php

namespace App\Console;

use App\Models\Job\SyncJobsHistory;
use App\Models\Job\SyncJobsLog;
use App\Models\Job\SyncJobsStatusUpdate;
use App\Queues\Jobs\SyncJob;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $days = env('KEEP_LOGS_FOR', 45);
            $timeAgo = Carbon::now()->subDays($days)->toDateTimeString();
            SyncJobsStatusUpdate::where('created_at', '<=', $timeAgo)->delete();
            SyncJobsLog::where('created_at', '<=', $timeAgo)->delete();
            SyncJobsHistory::where('created_at', '<=', $timeAgo)->delete();
        })
            ->timezone('Australia/Brisbane')
            ->daily()
            ->name('trim_logs')
            ->onOneServer();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
