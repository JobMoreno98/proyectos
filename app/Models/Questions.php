<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Questions extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    // Esto convierte el JSON de la BD a un Array de PHP automáticamente
    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_unique' => 'boolean',
    ];

    public function section()
    {
        return $this->belongsTo(Sections::class);
    }
}
