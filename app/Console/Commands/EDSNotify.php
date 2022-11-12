<?php

namespace App\Console\Commands;

use App\Queues\Jobs\ProcessRexEDSNotification;
use Illuminate\Console\Command;

class EDSNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify-connector:eds-notify {client} {salesChannel} {entity} {externalIds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate an EDS notification';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $clientExternalId = $this->argument('client');
        $rexSalesChannelExternalId = $this->argument('salesChannel');
        $entityType = $this->argument('entity');
        $externalIds = explode(',', $this->argument('externalIds'));

        $notification = new \stdClass();
        $notification->Type = $entityType;
        $notification->List = $externalIds;

        ProcessRexEDSNotification::dispatch($clientExternalId, $notification, $rexSalesChannelExternalId)
            ->onConnection('database_sync')
            ->onQueue('notification');
    }
}
