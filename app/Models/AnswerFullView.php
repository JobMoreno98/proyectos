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
        return $this->belongsTo(ViewDatosGenerales::class, 'user_id');
    }

    public function ciclo()
    {
        return $this->belongsTo(Ciclos::class);
    }

    protected function info(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => self::select('respuesta', 'fecha_creado', 'entry_id')
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
            // Intenta decodificar el valor como JSON
            $decoded = json_decode($valor, true);

            // Si se pudo decodificar y no es null, usa ese valor
            if ($decoded !== null) {
                return $decoded;
            }

            // Si no, elimina comillas sobrantes y devuelve el valor original
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
            ->where('section_title', self::SECTION_ASIGNACIONES)->where('entry_id', $targetId)
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
            ->where('section_title', self::SECTION_ASIGNACIONES)->where('entry_id', $this->entry_id)
            ->value('respuesta');


        $entry = AnswerFullView::select('entry_id', 'is_editable')
            ->where('pregunta', 'Proyecto')
            ->whereIn('section_id', [Sections::idEvaluacionNuevo(), Sections::idEvaluacionContinuacion()])
            ->where('respuesta', $proyecto_id)
            ->where('user_id', Auth::user()->id)->first();

        //dd($proyecto_id,[Sections::idEvaluacionNuevo(), Sections::idEvaluacionContinuacion()],Auth::user()->id);

        return $entry ?? null;
    }

    public function idPreguntas()
    {
        return Questions::whereIn('section_id', [Sections::idEvaluacionNuevo(), Sections::idEvaluacionContinuacion()])->where('label', 'Proyecto')->pluck('id');
    }

    public function obtenerCalificacionDeEvaluacion()
    {
        $preguntasEnlaceIds = self::idPreguntas();

        $enlace = AnswerFullView::whereIn('question_id', $preguntasEnlaceIds)
            ->where('respuesta', $this->entry_id)
            ->first();

        if (!$enlace) {
            return 'Sin evaluar';
        }
        if ($enlace->section_id == Sections::idEvaluacionNuevo()) {
            $multiplicador = 2.5;
        } else {
            $multiplicador = 4.16;
        }
        $evaluacionEntryId = $enlace->entry_id;

        $totalPuntos = 0;
        $hijosIds = [];

        $preguntasSubFormIds = Questions::where('type', 'sub_form')->pluck('id')->toArray();

        $respuestasPrincipales = AnswerFullView::where('entry_id', $evaluacionEntryId)
            ->whereIn('section_id', [
                Sections::idEvaluacionNuevo(),
                Sections::idEvaluacionContinuacion()
            ])->where('pregunta', '!=',  'Proyecto')
            ->get();
        foreach ($respuestasPrincipales as $item) {
            $valor = trim($item->respuesta);
            if (in_array($item->question_id, $preguntasSubFormIds)) {

                if (is_numeric($valor) && $valor !== '') {
                    $hijosIds[] = $valor;
                }
            } else {
                if (is_numeric($valor) && $valor !== '') {
                    $totalPuntos += (int) $valor;
                }
            }
        }

        if (!empty($hijosIds)) {

            $respuestasHijos = AnswerFullView::whereIn('entry_id', $hijosIds)->get();

            foreach ($respuestasHijos as $itemHijo) {
                $valorHijo = trim($itemHijo->respuesta);
                if (is_numeric($valorHijo) && $valorHijo !== '') {
                    $totalPuntos += (int) $valorHijo;
                }
            }
        }

        return $totalPuntos * $multiplicador ?? 'Sin evaluar';
    }

    public function getIdEvaluacionAttribute()
    {

        $entry = $this->entry_id;

        return AnswerFullView::select('entry_id')
            ->where('pregunta', 'Proyecto')
            ->whereIn('section_id', [Sections::idEvaluacionNuevo(), Sections::idEvaluacionContinuacion()])
            ->where('respuesta', $entry)
            ->value('entry_id');
    }

    public function getSubFormAttribute()
    {

        $entry = $this->entry_id;

        $sub_forms = AnswerFullView::where('tipo', 'sub_form')
            ->where('entry_id', $entry)->where('section_title', '!=', 'Archivos')
            ->pluck('respuesta')
            ->toArray();

        $evaluacion = AnswerFullView::where('tipo', 'sub_form')
            ->where('entry_id', $this->id_evaluacion)
            ->pluck('respuesta')
            ->toArray();


        $sub_forms = array_merge($evaluacion, $sub_forms);

        return AnswerFullView::whereIn('entry_id', $sub_forms)
            ->orderBy('entry_id')->pluck('respuesta', 'question_id');
    }
}
