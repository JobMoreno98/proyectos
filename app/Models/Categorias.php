<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categorias extends Model
{
    protected $guarded = [];
    const DATOS_GENERALES = 'Datos Generales';

    public function secciones()
    {
        return $this->hasMany(Sections::class, 'categoria_id');
    }

    public function isDatosGenerales(): bool
    {
        return $this->titulo === self::DATOS_GENERALES;
    }
    //
}
