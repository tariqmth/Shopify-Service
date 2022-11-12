<?php

namespace App\Console\Commands;

use App\Models\Client\Client;
use App\Models\Store\RexSalesChannel;
use App\Models\Store\ShopifyStore;
use App\Queues\Jobs\SyncRexMock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify-connector:test-performance {num_clients} {stores_per_client} {jobs_per_store}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test performance';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        for ($clientNum = 0; $clientNum < $this->argument('num_clients'); $clientNum++) {
            $client = new Client;
            $client->name = 'client-' . $clientNum;
            $client->licensed_stores = $this->argument('stores_per_client');
            $client->save();
            for ($storeNum = 0; $storeNum < $this->argument('stores_per_client'); $storeNum++) {
                $rexSalesChannel = new RexSalesChannel;
                $rexSalesChannel->name = 'channel-' . $clientNum . '-' . $storeNum;
                $rexSalesChannel->client_id = $client->id;
                $rexSalesChannel->save();
                $shopifyStore = new ShopifyStore;
                $shopifyStore->client_id = $client->id;
                $shopifyStore->rex_sales_channel_id = $rexSalesChannel->id;
                $shopifyStore->subdomain = 'shopify-store-' . $clientNum . '-' . $storeNum;
                $shopifyStore->enabled = true;
                $shopifyStore->setup_status = 'complete';
                $shopifyStore->currency = 'AUD';
                $shopifyStore->save();
            }
            for ($jobNum = 0; $jobNum < $this->argument('jobs_per_store'); $jobNum++) {
                SyncRexMock::dispatch($client, true, true)
                    ->onConnection('database_sync')
                    ->onQueue('product');
            }
        }
    }
}
