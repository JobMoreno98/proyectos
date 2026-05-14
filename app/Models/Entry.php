<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entry extends Model
{
    protected $guarded = [];
    use SoftDeletes; // <--- Activa la magia

    protected $dates = ['deleted_at'];

    protected $casts = [
        'is_editable' => 'boolean',
    ];

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function proyecto()
    {
        return $this->hasOne(Answer::class)
            ->whereHas('question')
            ->with('question.section'); // para que cargue también la pregunta
    }

    public function getCalificacionTotal()
    {
        $total = 0;

        foreach ($this->answers as $respuesta) {
            if (!blank($respuesta->value) && is_numeric($respuesta->value)) {
                $total += (float) $respuesta->value;
            }
        }

        $preguntasSubForm = Questions::where('type', 'sub_form')->pluck('id');

        $enlacesAHijos = $this->answers->whereIn('question_id', $preguntasSubForm);

        foreach ($enlacesAHijos as $enlace) {
            if (!blank($enlace->value) && is_numeric($enlace->value)) {
                $hijo = self::find($enlace->value);
                if ($hijo) {

                    $total += $hijo->getCalificacionTotal();
                }
            }
        }

        return $total;
    }
    public function obtenerCalificacionDeEvaluacion()
    {

        $preguntasSubFormIds = Questions::where('type', 'sub_form')->pluck('id')->toArray();

        $respuestasPrincipales = AnswerFullView::where('entry_id', $this->id)
            ->whereIn('section_id', [
                Sections::idEvaluacionNuevo(),
                Sections::idEvaluacionContinuacion()
            ])->where('pregunta', '!=',  'Proyecto')
            ->get();
        $seccion = AnswerFullView::where('entry_id', $this->id)->first();
        if ($seccion->section_id == null) {
            return null;
        }
        if ($seccion->section_id == Sections::idEvaluacionNuevo()) {
            $multiplicador = 2.5;
        } else {
            $multiplicador = 4.16;
        }

        $totalPuntos = 0;
        $hijosIds = [];

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
    public function idPreguntas()
    {
        return Questions::whereIn('section_id', [Sections::idEvaluacionNuevo(), Sections::idEvaluacionContinuacion()])->where('label', 'Proyecto')->pluck('id');
    }
}
