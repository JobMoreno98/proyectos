<?php

namespace App\Exports;

use App\Models\Questions;
use App\Models\AnswerFullView; // <-- TU NUEVO MODELO DE LA VISTA
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
            'Nombre',
            'Correo',
            'Ciclo'
        ];

        foreach ($this->preguntasConfig as $pregunta) {
            $encabezados[] = $pregunta->label;
        }

        return $encabezados;
    }

    // 2. LA MAGIA DE PIVOTAR DATOS
    public function array(): array
    {

        $respuestasPlanas = AnswerFullView::whereIn('question_id', $this->preguntaIds)->get();


        $respuestasAgrupadas = $respuestasPlanas->groupBy('user_id');

        $filasExcel = [];

        // Paso 3: Armamos las filas
        foreach ($respuestasAgrupadas as $userId => $respuestasUsuario) {

            // Tomamos el primer registro solo para sacar los datos generales del usuario
            $datosBase = $respuestasUsuario->first();

            // Iniciamos la fila con los datos fijos
            $fila = [
                $datosBase->user_name,
                $datosBase->user_email,
                $datosBase->ciclo->nombre,
            ];

            // Convertimos la colección del usuario en un diccionario [question_id => respuesta]
            $respuestasMapeadas = $respuestasUsuario->keyBy('question_id');

            // Recorremos las columnas (preguntas) para acomodar cada respuesta
            foreach ($this->preguntasConfig as $pregunta) {
                // Buscamos si este usuario contestó esta pregunta en específico
                $respuesta = $respuestasMapeadas->get($pregunta->id);

                // Ojo: En tu vista la columna se llama 'respuesta', no 'value'
                $valor = $respuesta ? trim($respuesta->respuesta) : '';

                // Si es un JSON (checkboxes múltiples), lo limpiamos
                // Si es un JSON (checkboxes múltiples), lo limpiamos
                if (is_string($valor) && str_starts_with($valor, '[')) {
                    $arreglo = json_decode($valor, true);

                    if (is_array($arreglo)) {
                        $collapsed = collect($arreglo)->collapse()->toArray();

                        // Aquí concatenamos directamente los valores que nos interesan
                        if (isset($collapsed['nombre'], $collapsed['tipo'])) {
                            $valor = $collapsed['nombre'] . ' - ' . $collapsed['tipo'];
                        } else {
                            // fallback si no existen esas claves
                            $valor = implode(' ', $collapsed);
                        }
                    }
                }

                // -> AQUÍ AGREGAS LA MAGIA <-
                // Limpiamos el HTML sin importar si venía de un texto normal o un checkbox decodificado
                $valor = $this->limpiarHtml($valor);

                $fila[] = $valor;
            }

            // Agregamos la fila terminada a nuestro Excel
            $filasExcel[] = $fila;
        }

        return $filasExcel;
    }
    // TRADUCTOR DE HTML A TEXTO PLANO
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

    // ACTIVAR EL "AJUSTAR TEXTO" EN EXCEL
    public function styles(Worksheet $sheet)
    {
        // Esto le dice a Excel que permita celdas de varias líneas en toda la hoja
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setWrapText(true);
    }
}
