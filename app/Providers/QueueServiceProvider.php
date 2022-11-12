<?php

namespace App\Providers;

use App\Queues\DatabaseFailedJobProvider;
use Illuminate\Support\ServiceProvider;
use App\Queues\Connectors\SyncConnector;
use Illuminate\Contracts\Debug\ExceptionHandler;
use App\Queues\Worker;
use Illuminate\Queue\Failed\NullFailedJobProvider;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $manager = $this->app['queue'];
        $manager->addConnector('database_sync', function()
        {
            return new SyncConnector($this->app['db']);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerWorker();
        $this->registerFailedJobServices();
    }

    /**
     * Register the queue worker.
     *
     * @return void
     */
    protected function registerWorker()
    {
        $this->app->extend('queue.worker', function () {
            return new Worker(
                $this->app['queue'], $this->app['events'], $this->app[ExceptionHandler::class]
            );
        });
    }

    /**
     * Register the failed job services.
     *
     * @return void
     */
    protected function registerFailedJobServices()
    {
        $this->app->extend('queue.failer', function () {
            $config = $this->app['config']['queue.failed'];

            return isset($config['table'])
                        ? $this->databaseFailedJobProvider($config)
                        : new NullFailedJobProvider;
        });
    }

    /**
     * Create a new database failed job provider.
     *
     * @param  array  $config
     * @return \App\Queues\DatabaseFailedJobProvider
     */
    protected function databaseFailedJobProvider($config)
    {
        return new DatabaseFailedJobProvider(
            $this->app['db'], $config['database'], $config['table']
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'queue.worker',
            'queue.failer'
        ];
    }
}
