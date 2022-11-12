<?php

namespace App\Logging\Monolog;

use App\Models\Job\SyncJobsRepository;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class DatabaseHandler extends AbstractProcessingHandler {

    protected $syncJobsRepository;

    public function __construct(
        SyncJobsRepository $syncJobsRepository,
        $level = Logger::DEBUG,
        $bubble = true
    ) {
        $this->syncJobsRepository = $syncJobsRepository;
        parent::__construct($level, $bubble);
    }

    protected function write(array $record) {
        try {
            $queueWorker = app('queue.worker');
            $currentJobHistory = $queueWorker->getRunningJobHistory();
            $jobHistoryUniqueId = isset($currentJobHistory) ? $currentJobHistory->unique_id : null;
            $context = $record['context'];

            if (isset($context['exception'])) {
                $exception = $context['exception'];
                $context['message'] = $exception->getMessage();
                $context['trace'] = $exception->getTrace();
                unset($context['exception']);
            }

            $this->syncJobsRepository->log(
                $jobHistoryUniqueId,
                $record['channel'],
                substr($record['message'], 0, 5000),
                $record['level_name'],
                json_encode($context)
            );
        } catch (\Exception $e) {
            Log::channel('single')->error('Could not write to database log: ' . $e);
        }
    }
}
