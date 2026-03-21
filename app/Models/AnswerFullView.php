<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Auth;

class AnswerFullView extends Model
{
    protected $table = 'answers_view';

    public $timestamps = false;

    const SECTION_ASIGNACIONES = 'Asignaciones';
    const PREGUNTA_PROYECTO    = 'Proyecto';
    const PREGUNTA_EVALUADOR   = 'Evaluador';

    protected $casts = [
        'is_editable' => 'boolean',
        'fecha_creado' => 'date',
    ];

    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }

    public function user()
    {
        return $this->belongsTo(ViewDatosGenerales::class,'user_id')->select('name');
    }

    public function ciclo()
    {
        return $this->belongsTo(Ciclos::class);
    }

    protected function info(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => self::select('respuesta', 'fecha_creado')
                ->where('pregunta', 'Proyecto')
                ->where('entry_id', $attributes['entry_id'])->first()
        );
    }

    protected function getDataAttribute()
    {
        $targetId = $this->entry_id;

        $nombresPreguntas = ['Título del Proyecto', 'Folio'];

        $respuestas = AnswerFullView::where('entry_id', $targetId)
            ->whereIn('pregunta', $nombresPreguntas)
            ->pluck('respuesta', 'pregunta');

        return [
            'titulo'      => $respuestas['Título del Proyecto'] ?? 'N/A',
            'folio' => $respuestas['Folio'] ?? 'N/A',
        ];
    }

    public function getEvalaudorDataAttribute()
    {
        $targetId = $this->entry_id;

        $evalaudor = self::asignaciones()
            ->pregunta('Evaluador')
            ->whereIn('entry_id', function ($query) use ($targetId) {
                $query->select('entry_id')
                    ->from('answers_view')
                    ->where('pregunta', 'Proyecto')
                    ->where('section_title', 'Asignaciones')
                    ->where('respuesta', $targetId);
            })
            ->leftJoin(
                'view_datos_generales',
                'answers_view.respuesta',
                '=',
                'view_datos_generales.user_id'
            )
            ->first();

        if (!isset($evalaudor->datos_json)) {
            return [];
        }

        $datos = json_decode($evalaudor->datos_json, true);
        $datos = array_map(function ($valor) {
            return trim($valor, '"');
        }, $datos);

        $keys =  array_map(['App\Models\AnswerFullView', 'slugify'], array_keys($datos));

        $datos_slug = array_combine($keys, $datos);

        return collect($datos_slug);
    }

    private static function slugify($text)
    {
        $text = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $text
        );

        // Pasar a minúsculas
        $text = strtolower($text);

        // Reemplazar cualquier cosa que no sea letras/números por guiones
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        // Quitar guiones al inicio/fin
        return trim($text, '-');
    }

    public function getAsignacionAttribute()
    {

        $targetId = $this->entry_id;
        //dd($targetId);
        $nombresPreguntas = ['Proyecto'];

        $entry_id = AnswerFullView::where('respuesta', $targetId)
            ->whereIn('pregunta', $nombresPreguntas)
            ->where('section_title', self::SECTION_ASIGNACIONES)
            ->value('entry_id');

        return $entry_id ?? null;
    }


    public function scopeAsignaciones($query)
    {
        return $query->where('section_title', 'Asignaciones');
    }

    public function scopePregunta($query, $pregunta)
    {
        return $query->where('pregunta', $pregunta);
    }

    public function scopeRespuesta($query, $respuesta)
    {
        return $query->where('respuesta', $respuesta);
    }

    public function getAsignadoAttribute()
    {

        $targetId = $this->entry_id;

        $nombresPreguntas = ['Proyecto'];

        $entry_id = AnswerFullView::whereIn('pregunta', $nombresPreguntas)
            ->where('section_title', self::SECTION_ASIGNACIONES)
            ->value('respuesta');

        $nombresPreguntas = ['Título del Proyecto', 'Folio'];

        $respuestas = AnswerFullView::where('entry_id', $entry_id)
            ->whereIn('pregunta', $nombresPreguntas)
            ->pluck('respuesta', 'pregunta');

        return [
            'titulo'      => $respuestas['Título del Proyecto'] ?? 'N/A',
            'folio' => $respuestas['Folio'] ?? 'N/A',
        ];

        // return $entry_id ?? null;
    }

    public function getEvaluacionAttribute()
    {

        $nombresPreguntas = ['Proyecto'];

        $proyecto_id = AnswerFullView::whereIn('pregunta', $nombresPreguntas)
            ->where('section_title', self::SECTION_ASIGNACIONES)
            ->value('respuesta');


        $entry = AnswerFullView::select('entry_id', 'is_editable')->where('pregunta', 'Proyecto')->where('respuesta', $proyecto_id)->where('user_id', Auth::user()->id)->first();

        return $entry ?? null;
    }
}
