<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ciclos extends Model
{
    protected $guarded = [];
    protected $casts = [
        'sistemas' => 'array',
        'activo' => 'boolean'
    ];
}
