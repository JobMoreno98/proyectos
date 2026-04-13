<?php

namespace App\Exports;

use App\Models\Questions;
use App\Models\AnswerFullView;
use App\Models\Sections;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RespuestasExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithColumnWidths
{
    protected $preguntaIds;
    protected $preguntasConfig;

    private $cacheLimpieza = [];


    private $dataEvaluaciones;
    private $preguntasSubFormIds;

    public function __construct($preguntaIds)
    {
        $this->preguntaIds = $preguntaIds;

        $this->preguntasConfig = Questions::whereIn('id', $this->preguntaIds)
            ->orderBy('section_id')
            ->orderBy('sort_order')
            ->get();
    }

    public function headings(): array
    {
        $encabezados = [
            'Código',
            'Nombre',
            'Ciclo',
            'Calificación'
        ];

        foreach ($this->preguntasConfig as $pregunta) {
            $encabezados[] = $pregunta->label;
        }

        return $encabezados;
    }

    public function array(): array
    {
        $filasExcel = [];

        $respuestasPlanas = AnswerFullView::whereIn('question_id', $this->preguntaIds)
            ->select('entry_id', 'question_id', 'respuesta')
            ->get()
            ->groupBy('entry_id')
            ->map(fn($items) => $items->pluck('respuesta', 'question_id'));

        $entryIds = $respuestasPlanas->keys();


        $proyectosPrincipales = AnswerFullView::whereIn('entry_id', $entryIds)
            ->where('section_title', 'Proyectos de Investigación')
            ->with(['user', 'ciclo'])
            ->get()
            ->unique('entry_id');

        $evaluacionesMap = AnswerFullView::where('pregunta', 'Proyecto')
            ->whereIn('respuesta', $entryIds)
            ->whereIn('section_id', [
                Sections::idEvaluacionNuevo(),
                Sections::idEvaluacionContinuacion()
            ])
            ->pluck('entry_id', 'respuesta');

        $evaluacionIds = $evaluacionesMap->values();

        $subForms = AnswerFullView::where('tipo', 'sub_form')
            ->where(function ($q) use ($entryIds, $evaluacionIds) {
                $q->whereIn('entry_id', $entryIds)
                    ->orWhereIn('entry_id', $evaluacionIds);
            })
            ->get()
            ->groupBy('entry_id');

        $subFormIds = $subForms->flatten()->pluck('respuesta')->filter()->unique();

        $subFormsRespuestas = AnswerFullView::whereIn('entry_id', $subFormIds)
            ->get()
            ->groupBy('entry_id');

        $respuestasEvaluacion = AnswerFullView::whereIn('entry_id', $evaluacionIds)
            ->get()
            ->groupBy('entry_id');

        $preguntasSubFormIds = Questions::where('type', 'sub_form')->pluck('id')->toArray();


        foreach ($proyectosPrincipales as $proyecto) {

            $user = $proyecto->user;

            $nombreCompleto = mb_strtoupper(trim(
                ($user->datos_limpios['Apellido Paterno'] ?? '') . " " .
                    ($user->datos_limpios['Apellido Materno'] ?? '') . " " .
                    ($user->datos_limpios['Nombres'] ?? '')
            ), 'UTF-8');

            $evaluacionId = $evaluacionesMap[$proyecto->entry_id] ?? null;

            $calificacion = 'Sin evaluar';

            if ($evaluacionId && isset($respuestasEvaluacion[$evaluacionId])) {

                $multiplicador = 4.1666; // default

                foreach ($respuestasEvaluacion[$evaluacionId] as $item) {
                    if ($item->pregunta === 'Proyecto') {
                        $multiplicador = $item->section_id == Sections::idEvaluacionNuevo() ? 2.5 : 4.16666;

                        break;
                    }
                }

                $total = 0;
                $hijosIds = [];

                foreach ($respuestasEvaluacion[$evaluacionId] as $item) {
                    $valor = trim($item->respuesta);
                    if (in_array($item->question_id, $preguntasSubFormIds)) {
                        if (is_numeric($valor)) $hijosIds[] = $valor;
                    }
                }

                foreach ($hijosIds as $id) {
                    if (isset($subFormsRespuestas[$id])) {
                        foreach ($subFormsRespuestas[$id] as $hijo) {
                            if (is_numeric($hijo->respuesta)) {
                                $total += (int) $hijo->respuesta;
                                //echo "Sumado " . $hijo->respuesta . "<br/>";
                                //echo "Total " . $total . "<br/>";
                            }
                        }
                    }
                }
                //dd($hijosIds, $total, $multiplicador, $total * $multiplicador);

                $calificacion = $total * $multiplicador;
                if ($calificacion >= 80) {
                    $calificacion = $calificacion . " /  Aprobado";
                } elseif ($calificacion >= 60) {
                    $calificacion = $calificacion . " /  Revisión";
                } else {
                    $calificacion = $calificacion . " /  Deficiente";
                }
            }

            $fila = [
                $user->datos_limpios['Código'] ?? '',
                $nombreCompleto,
                $proyecto->ciclo->nombre ?? '',
                $calificacion,
            ];

            $respuestasEntry = $respuestasPlanas->get($proyecto->entry_id, collect());

            $subFormsEntry = collect();

            $ids = [];

            if (isset($subForms[$proyecto->entry_id])) {
                $ids = array_merge(
                    $ids,
                    $subForms[$proyecto->entry_id]
                        ->where('section_title', '!=', 'Archivos')
                        ->pluck('respuesta')
                        ->toArray()
                );
            }

            if ($evaluacionId && isset($subForms[$evaluacionId])) {
                $ids = array_merge(
                    $ids,
                    $subForms[$evaluacionId]->pluck('respuesta')->toArray()
                );
            }

            foreach ($ids as $id) {
                if (isset($subFormsRespuestas[$id])) {
                    foreach ($subFormsRespuestas[$id] as $item) {
                        $subFormsEntry[$item->question_id] = $item->respuesta;
                    }
                }
            }

            $dataMerged = [];

            foreach ($subFormsEntry as $k => $v) {
                $dataMerged[$k] = $v;
            }

            foreach ($respuestasEntry as $k => $v) {
                if (!isset($dataMerged[$k])) {
                    $dataMerged[$k] = $v;
                }
            }

            $dataMerged = collect($dataMerged);

            //dd($respuestasPlanas, $evaluacionesMap, $dataMerged, $respuestasPlanas->get($proyecto->entry_id, collect()));

            foreach ($this->preguntasConfig as $pregunta) {
                $valor = $dataMerged->get($pregunta->id, 'Sin respuesta');
                $fila[] = $this->limpiarHtml($valor);
            }

            $filasExcel[] = $fila;
        }

        return $filasExcel;
    }


    private function limpiarHtml($texto)
    {
        if (!is_string($texto) || empty($texto)) return $texto;

        $hash = md5($texto);
        if (isset($this->cacheLimpieza[$hash])) return $this->cacheLimpieza[$hash];

        $textoLimpio = str_ireplace(
            ['<br>', '<br/>', '<br />', '</p>', '</li>', '</ul>', '</h1>', '</h2>', '</h3>'],
            "\n",
            $texto
        );

        $textoLimpio = strip_tags($textoLimpio);
        $textoLimpio = html_entity_decode($textoLimpio, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $textoLimpio = trim(preg_replace("/\n+/", "\n", $textoLimpio));

        return $this->cacheLimpieza[$hash] = $textoLimpio;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle($sheet->calculateWorksheetDimension())
            ->getAlignment()
            ->setWrapText(true);
    }
    public function columnWidths(): array
    {
        $totalColumnas = 4 + count($this->preguntasConfig);

        $widths = [];

        for ($i = 0; $i < $totalColumnas; $i++) {
            $col = $this->getColumnLetter($i);
            $widths[$col] = 15;
        }

        return $widths;
    }
    private function getColumnLetter($index)
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr($index % 26 + 65) . $letter;
            $index = intdiv($index, 26) - 1;
        }
        return $letter;
    }
}
