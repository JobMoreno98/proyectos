<?php

namespace App\Exports;

use App\Models\Questions;
use App\Models\AnswerFullView; // <-- TU NUEVO MODELO DE LA VISTA
use App\Models\ViewDatosGenerales;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RespuestasExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $preguntaIds;
    protected $preguntasConfig;

    public function __construct($preguntaIds)
    {
        $this->preguntaIds = $preguntaIds;

        $this->preguntasConfig = Questions::whereIn('id', $this->preguntaIds)
            ->orderBy('section_id')
            ->orderBy('sort_order')
            ->get();
    }

    // 1. ENCABEZADOS (Aprovechamos los datos extra de tu vista)
    public function headings(): array
    {
        // Tu vista ya nos da el nombre y correo, ¡pongámoslos en el Excel!
        $encabezados = [
            'Código',
            'Nombre',
            'Correo',
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

        // 1. Traemos TODAS las respuestas base de un jalón
        $respuestasPlanas = AnswerFullView::whereIn('question_id', $this->preguntaIds)->get();

        // ¡TRUCO PRO! Agrupamos las respuestas en la memoria RAM por entry_id
        $respuestasPorEntry = $respuestasPlanas->groupBy('entry_id');

        $entryIds = $respuestasPlanas->pluck('entry_id')->unique();

        $proyectosPrincipales = AnswerFullView::whereIn('entry_id', $entryIds)
            ->where('section_title', 'Proyectos de Investigación')
            ->groupBy('entry_id')
            ->get();

        $userIds = $proyectosPrincipales->pluck('user_id')->unique();
        $usuariosPrecargados = ViewDatosGenerales::whereIn('user_id', $userIds)->get()->keyBy('user_id');

        // B. Precargamos TODOS los enlaces de evaluación en una sola consulta
        $enlacesPrecargados = AnswerFullView::whereIn('question_id', (new AnswerFullView)->idPrguntas())
            ->whereIn('respuesta', $proyectosPrincipales->pluck('entry_id'))
            ->get()
            ->keyBy('respuesta');

        foreach ($proyectosPrincipales as $proyecto) {

            // ¡Lectura desde la RAM ultra rápida! Ya no tocamos la base de datos aquí.
            $datos_user = $usuariosPrecargados->get($proyecto->user_id);

            $nombreCompleto = trim(($datos_user->datos_limpios['Apellido Paterno'] ?? '') . " " .
                ($datos_user->datos_limpios['Apellido Materno'] ?? '') . " " .
                ($datos_user->datos_limpios['Nombres'] ?? ''));

            $fila = [
                $datos_user->datos_limpios['Código'] ?? '',
                mb_strtoupper($nombreCompleto, 'UTF-8'),
                $proyecto->user_email,
                $proyecto->ciclo->nombre ?? '',
            ];
            $fila[] = $proyecto->obtenerCalificacionDeEvaluacion();

            // ¡Lectura desde la RAM! Buscamos las respuestas del proyecto
            $respuestasProyecto = $respuestasPorEntry->get($proyecto->entry_id);
            $respuestasProyecto = $respuestasProyecto ? $respuestasProyecto->keyBy('question_id') : collect();

            // ¡Lectura desde la RAM! Buscamos si tiene enlace de evaluación
            $enlace = $enlacesPrecargados->get($proyecto->entry_id);

            $respuestasEvaluacion = collect();
            if ($enlace) {
                // ¡Lectura desde la RAM! Buscamos las respuestas del evaluador
                $eval = $respuestasPorEntry->get($enlace->entry_id);
                $respuestasEvaluacion = $eval ? $eval->keyBy('question_id') : collect();
            }

            // 4. Armamos las columnas fusionando ambos mundos
            foreach ($this->preguntasConfig as $pregunta) {

                $respuesta = $respuestasProyecto->get($pregunta->id) ?? $respuestasEvaluacion->get($pregunta->id);
                $valor = $respuesta ? trim($respuesta->respuesta) : '';

                if (is_string($valor) && str_starts_with($valor, '[')) {
                    $arreglo = json_decode($valor, true);
                    if (is_array($arreglo)) {
                        $collapsed = collect($arreglo)->collapse()->toArray();
                        $valor = isset($collapsed['nombre'], $collapsed['tipo'])
                            ? $collapsed['nombre'] . ' - ' . $collapsed['tipo']
                            : implode(' ', $collapsed);
                    }
                }

                $fila[] = $this->limpiarHtml($valor);
            }

            // Calculamos la calificación matemática final


            $filasExcel[] = $fila;
        }

        return $filasExcel;
    }

    private function limpiarHtml($texto)
    {
        if (!is_string($texto) || empty($texto)) {
            return $texto;
        }

        // 1. Reemplazamos cierres de párrafo, listas y saltos HTML por saltos de línea reales (\n)
        $texto = str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</li>', '</ul>', '</h1>', '</h2>', '</h3>'], "\n", $texto);

        // 2. Destruimos cualquier otra etiqueta HTML sobrante (negritas, cursivas, tablas, etc.)
        $texto = strip_tags($texto);

        // 3. Traducimos entidades especiales (ej. &nbsp; a espacio, &aacute; a á)
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 4. Limpiamos saltos de línea duplicados para que no queden huecos gigantes
        $texto = trim(preg_replace("/\n+/", "\n", $texto));

        return $texto;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setWrapText(true);
    }
}
