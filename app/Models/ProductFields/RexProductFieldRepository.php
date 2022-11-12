<?php

namespace App\Models\ProductFields;

class RexProductFieldRepository
{
    public function get($name)
    {
        return RexProductField::where('name', $name)->first();
    }

    public function getAll()
    {
        return RexProductField::all();
    }
}