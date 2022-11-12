<?php

namespace App\Models\Syncer;

use App\Models\Syncable;

class SyncerRepository
{
    public function getSyncer(Syncable $object)
    {
        $reflection = new \ReflectionObject($object);
        $className = $reflection->getShortName();
        $syncer = __NAMESPACE__ . '\\' . $className . 'Syncer';
        if (class_exists($syncer)) {
            return app()->make($syncer);
        } else {
            throw new \Exception('Syncer not found for ' . $className);
        }
    }
}