<?php

namespace App\Updaters;

use App\Models\Store\ShopifyStore;
use App\Models\Syncer\RexCustomerSyncer;
use App\Models\Syncer\ShopifyStoreSyncer;

class UpdaterFor10729PrimaryLocation implements Updater
{
    const NAME = '10729PrimaryLocation';

    protected $shopifyStoreSyncer;

    public function __construct(ShopifyStoreSyncer $shopifyStoreSyncer)
    {
        $this->shopifyStoreSyncer = $shopifyStoreSyncer;
    }

    public function run()
    {
        $shopifyStores = ShopifyStore::all();

        foreach ($shopifyStores as $shopifyStore) {
            $this->shopifyStoreSyncer->syncOut($shopifyStore);
        }
    }

    public function getName()
    {
        return self::NAME;
    }
}