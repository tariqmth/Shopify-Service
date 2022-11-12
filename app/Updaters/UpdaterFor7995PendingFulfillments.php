<?php

namespace App\Updaters;

use App\Models\Notification\ShopifyWebhookRepository;
use App\Models\Store\ShopifyStore;
use App\Models\Syncer\ShopifyWebhookSyncer;

class UpdaterFor7995PendingFulfillments implements Updater
{
    const NAME = '7995PendingFulfillments';

    protected $shopifyWebhookRepository;
    protected $shopifyWebhookSyncer;

    public function __construct(
        ShopifyWebhookRepository $shopifyWebhookRepository,
        ShopifyWebhookSyncer $shopifyWebhookSyncer
    ) {
        $this->shopifyWebhookRepository = $shopifyWebhookRepository;
        $this->shopifyWebhookSyncer = $shopifyWebhookSyncer;
    }

    public function run()
    {
        foreach (ShopifyStore::all() as $shopifyStore) {
            $unsyncedWebhooks = $this->shopifyWebhookRepository->updateAll($shopifyStore->id);
            foreach ($unsyncedWebhooks as $unsyncedWebhook) {
                $this->shopifyWebhookSyncer->syncIn($unsyncedWebhook);
            }
        }
    }

    public function getName()
    {
        return self::NAME;
    }
}