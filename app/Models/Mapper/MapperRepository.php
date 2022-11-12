<?php

namespace App\Models\Mapper;

use App\Models\Syncable;

class MapperRepository
{
    public function getMapper(Syncable $master, Syncable $slave)
    {
        $masterReflection = new \ReflectionObject($master);
        $masterClass = $masterReflection->getShortName();
        $masterClassWords = preg_split('/(?=[A-Z])/', $masterClass);
        $masterSource = $masterClassWords[0];
        $slaveReflection = new \ReflectionObject($slave);
        $slaveClass = $slaveReflection->getShortName();
        $mapper = __NAMESPACE__ . '\\' . $masterSource . 'To' . $slaveClass . 'Mapper';
        if (class_exists($mapper)) {
            return app()->make($mapper);
        } else {
            throw new \Exception('Mapper not found for ' . $masterClass . ' to ' . $slaveClass);
        }
    }
}