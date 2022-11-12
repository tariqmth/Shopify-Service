<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class RexInventoryBufferGroup extends Model
{
    // Defining one-to-many relationship
    // A buffer group can have many inventory buffer group mappings 
    // based on foreign key 'group_id' where local key is 'id' 
    
    public function rexInventoryBufferGroupMappings()
    {
        return $this->hasMany('App\Models\Inventory\RexInventoryBufferGroupMapping','group_id','id');
    }           
}
