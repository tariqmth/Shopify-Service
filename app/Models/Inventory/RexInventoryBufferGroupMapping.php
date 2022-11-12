<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class RexInventoryBufferGroupMapping extends Model
{
    // Defining The Inverse Of The Relation
    // a relationship to allow a inventory buffer group mapping to access its parent buffer group
    // A buffer group mapping belongs to one inventory buffer group 
    // based on foreign key 'group_id'  
    
    public function rexInventoryBufferGroup()
    {
           return $this->belongsTo('App\Models\Inventory\RexInventoryBufferGroup','group_id');
    }
}
