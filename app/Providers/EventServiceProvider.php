<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Log;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        if (env('LOG_DB_QUERIES')) {
            Event::listen('Illuminate\Database\Events\QueryExecuted', function ($query) {
                // filter oauth
                if (!str_contains($query->sql, 'oauth')) {
                    Log::channel('single')->debug(
                        $query->time . 'ms - '
                        . $query->sql . ' - '
                        . implode(', ', $query->bindings)
                    );
                }
            });
        }
    }
}
