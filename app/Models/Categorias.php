<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categorias extends Model
{
    protected $guarded = [];

    public function secciones()
    {
        return $this->hasMany(Sections::class, 'categoria_id');
    }
}
