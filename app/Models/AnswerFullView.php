<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


class AnswerFullView extends Model
{
    protected $table = 'answers_view';

    public $timestamps = false;
    protected $appends = ['proyecto'];

    protected $casts = [
        'is_editable' => 'boolean',
        'fecha_creado' => 'date',
    ];
    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }
    protected function info(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => self::select('respuesta', 'fecha_creado')
                ->where('pregunta', 'Proyecto')
                ->where('entry_id', $attributes['entry_id'])->first()
        );
    }

    protected function titulo_proyecto(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => self::select('respuesta', 'fecha_creado')
                //->where('pregunta', 'Título del Proyecto')
                ->where('entry_id', $attributes['entry_id'])->first()
        );
    }


    public function getProyectoAttribute()
    {
        $targetId = trim(str_replace(['"', "'"], '', $this->respuesta));

        $nombresPreguntas = ['Título del Proyecto', 'Folio'];

        $respuestas = AnswerFullView::where('entry_id', $targetId)
            ->whereIn('pregunta', $nombresPreguntas)
            ->pluck('respuesta', 'pregunta');
        dd("Entry", $this);

        // Retorno
        return [
            'titulo'      => $respuestas['Título del Proyecto'] ?? 'N/A',
            'folio' => $respuestas['Folio'] ?? 'N/A',
        ];
    }
}
