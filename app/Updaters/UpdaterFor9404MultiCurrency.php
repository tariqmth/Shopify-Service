<?php

namespace App\Updaters;

use App\Models\Notification\ShopifyWebhookRepository;
use App\Models\Store\ShopifyStore;
use App\Models\Syncer\ShopifyStoreSyncer;
use App\Models\Syncer\ShopifyWebhookSyncer;

class UpdaterFor9404MultiCurrency implements Updater
{
    const NAME = '9404MultiCurrency';

    protected $shopifyStoreSyncer;
    protected $shopifyWebhookRepository;
    protected $shopifyWebhookSyncer;

    public function __construct(
        ShopifyStoreSyncer $shopifyStoreSyncer,
        ShopifyWebhookRepository $shopifyWebhookRepository,
        ShopifyWebhookSyncer $shopifyWebhookSyncer
    ) {
        $this->shopifyStoreSyncer = $shopifyStoreSyncer;
        $this->shopifyWebhookRepository = $shopifyWebhookRepository;
        $this->shopifyWebhookSyncer = $shopifyWebhookSyncer;
    }

    public function run()
    {
        $shopifyStores = ShopifyStore::all();
        foreach ($shopifyStores as $shopifyStore) {
            $this->shopifyStoreSyncer->syncOut($shopifyStore);
            $webhooksToSyncIn = $this->shopifyWebhookRepository->updateAll($shopifyStore->id);
            foreach ($webhooksToSyncIn as $webhookToSyncIn) {
                $this->shopifyWebhookSyncer->syncIn($webhookToSyncIn);
            }
        }
    }

    public function getName()
    {
        return self::NAME;
    }
}