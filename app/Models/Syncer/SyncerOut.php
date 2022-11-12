<?php

namespace App\Models\Syncer;

use App\Models\Syncable;
use App\Models\Mapper\MapperRepository;
use App\Models\Product\ShopifyProduct;

abstract class SyncerOut
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

    abstract protected function getSlaves(Syncable $master);

    public function syncOut(Syncable $master, $masterData)
    {
        $mapper = $this->mapperRepository->getMapper($master);
        $slaves = $this->getSlaves($master);
        foreach ($slaves as $slave) {
            $mapper->map($master, $slave, $masterData);
            $slaveSyncer = $this->syncerRepository->getSyncerIn($slave);
            $slaveSyncer->queueSyncIn($slave);
        }
    }
}
