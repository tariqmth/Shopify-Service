<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class Syncable extends Model
{
    public function hasBeenSynced()
    {
        return isset($this->external_id);
    }
}