<?php

namespace App\Models\Syncer;

use App\Models\Syncable;
use App\Models\Mapper\MapperRepository;
use App\Models\Product\ShopifyProduct;

abstract class SyncerIn
{
    protected $modelRepository;
    protected $mapperRepository;
    protected $syncerRepository;

    public function __construct(
        MapperRepository $mapperRepository,
        SyncerRepository $syncerRepository
    ) {
        $this->mapperRepository = $mapperRepository;
        $this->syncerRepository = $syncerRepository;
    }

    abstract public function queueSyncIn(Syncable $slave, $slaveData);
}
