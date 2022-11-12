<?php

namespace App\Queues\Jobs;

interface SyncJob
{
    public function getSource();

    public function getEntityId();

    public function getEntityExternalId();

    public function getDirection();

    public function getClientId();

    public function getShopifyStoreId();
}
