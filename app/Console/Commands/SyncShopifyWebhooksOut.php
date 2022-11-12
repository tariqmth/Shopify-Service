<?php

namespace App\Console\Commands;

use App\Models\Store\ShopifyStore;
use App\Models\Syncer\ShopifyWebhookSyncer;
use Illuminate\Console\Command;

class SyncShopifyWebhooksOut extends Command
{
    const WEBHOOKS_QUEUED_MESSAGE = 'Webhooks for Shopify store %d "%s" have been queued to sync out from Shopify.';
    const WEBHOOKS_SYNCED_MESSAGE = 'Webhooks for Shopify store %d "%s" have been synced out from Shopify.';

    private $shopifyWebhookSyncer;

    public function __construct(
        ShopifyWebhookSyncer $shopifyWebhookSyncer
    ) {
        parent::__construct();
        $this->shopifyWebhookSyncer = $shopifyWebhookSyncer;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:sync-webhooks-out {store_id?} {--queue} {--recurring}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify existing webhooks for Shopify store ID';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->argument('store_id') !== null) {
            $shopifyStore = ShopifyStore::findOrFail($this->argument('store_id'));
            $this->sync($shopifyStore);
        } else {
            $shopifyStores = ShopifyStore::all();
            foreach ($shopifyStores as $shopifyStore) {
                $this->sync($shopifyStore);
            }
        }
    }

    private function sync(ShopifyStore $shopifyStore)
    {
        if ($this->option('queue')) {
            $this->shopifyWebhookSyncer->syncOut($shopifyStore, $this->option('recurring'));
            $this->info(sprintf(self::WEBHOOKS_QUEUED_MESSAGE, $shopifyStore->id, $shopifyStore->subdomain));
        } else {
            $this->shopifyWebhookSyncer->performSyncOut($shopifyStore->id);
            $this->info(sprintf(self::WEBHOOKS_SYNCED_MESSAGE, $shopifyStore->id, $shopifyStore->subdomain));
        }
    }
}
