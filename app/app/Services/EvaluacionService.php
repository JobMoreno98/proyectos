<?php

namespace App\Services;

use App\Models\AnswerFullView;
use App\Models\Questions;
use App\Models\Sections;

class EvaluacionService
{
    protected $data;
    protected $preguntasSubFormIds;

    public function __construct()
    {
        $this->preguntasSubFormIds = Questions::where('type', 'sub_form')->pluck('id')->toArray();
    }

    protected function cargarDatos($entryId)
    {
        if ($this->data) {
            return;
        }

        $preguntasEnlaceIds = AnswerFullView::idPreguntas();

        $this->data = AnswerFullView::where(function ($q) use ($preguntasEnlaceIds) {
            $q->whereIn('question_id', $preguntasEnlaceIds);
        })
            ->orWhere(function ($q) {
                $q->whereIn('section_id', [
                    Sections::idEvaluacionNuevo(),
                    Sections::idEvaluacionContinuacion()
                ]);
            })
            ->get();
    }

    public function obtenerCalificacion($entryId)
    {
        $this->cargarDatos($entryId);

        $enlace = $this->data->firstWhere('respuesta', $entryId);

        if (!$enlace) {
            return 'Sin evaluar';
        }

        $multiplicador = $enlace->section_id == Sections::idEvaluacionNuevo() ? 2.5 : 3.57;
        $evaluacionEntryId = $enlace->entry_id;

        $totalPuntos = 0;
        $hijosIds = [];

        $respuestasPrincipales = $this->data->where('entry_id', $evaluacionEntryId)
            ->whereIn('section_id', [
                Sections::idEvaluacionNuevo(),
                Sections::idEvaluacionContinuacion()
            ])
            ->where('pregunta', '!=', 'Proyecto');

        foreach ($respuestasPrincipales as $item) {
            $valor = trim($item->respuesta);

            if (in_array($item->question_id, $this->preguntasSubFormIds)) {
                if (is_numeric($valor)) {
                    $hijosIds[] = $valor;
                }
            } else {
                if (is_numeric($valor)) {
                    $totalPuntos += (int) $valor;
                }
            }
        }

        if (!empty($hijosIds)) {
            $respuestasHijos = $this->data->whereIn('entry_id', $hijosIds);

            foreach ($respuestasHijos as $item) {
                $valor = trim($item->respuesta);
                if (is_numeric($valor)) {
                    $totalPuntos += (int) $valor;
                }
            }
        }

        return $totalPuntos * $multiplicador;
    }

    public function getIdEvaluacion($entryId)
    {
        $this->cargarDatos($entryId);

        return $this->data
            ->where('pregunta', 'Proyecto')
            ->whereIn('section_id', [
                Sections::idEvaluacionNuevo(),
                Sections::idEvaluacionContinuacion()
            ])
            ->where('respuesta', $entryId)
            ->value('entry_id');
    }

    public function getSubForms($entryId)
    {
        $this->cargarDatos($entryId);

        $subForms = $this->data
            ->where('tipo', 'sub_form')
            ->where('entry_id', $entryId)
            ->where('section_title', '!=', 'Archivos')
            ->pluck('respuesta')
            ->toArray();

        $evaluacionId = $this->getIdEvaluacion($entryId);

        $evaluacionForms = $this->data
            ->where('tipo', 'sub_form')
            ->where('entry_id', $evaluacionId)
            ->pluck('respuesta')
            ->toArray();

        $ids = array_merge($subForms, $evaluacionForms);

        return $this->data
            ->whereIn('entry_id', $ids)
            ->sortBy('entry_id')
            ->pluck('respuesta', 'question_id');
    }
}
