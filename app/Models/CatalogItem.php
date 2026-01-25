<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    protected $guarded = [];
    public function scopeByType($query, $type)
    {
        return $query->where('catalog_type', $type)->where('is_active', true);
    }
}
