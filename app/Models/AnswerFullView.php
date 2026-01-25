<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


class AnswerFullView extends Model
{
    protected $table = 'answers_view';

    public $timestamps = false;

    protected $casts = [
        'is_editable' => 'boolean',
        'fecha_creado' => 'date'
    ];
    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }
    protected function titulo(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => self::select('respuesta', 'fecha_creado')
                //->where('pregunta', 'Título del Proyecto')
                ->where('entry_id', $attributes['entry_id'])->first()
        );
    }
}
