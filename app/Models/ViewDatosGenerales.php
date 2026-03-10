<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewDatosGenerales extends Model
{
    // Apuntamos a la vista, no a una tabla física
    protected $table = 'view_datos_generales';

    // Como es una vista, no se puede insertar/editar
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'user_id';

    protected $casts = [
        'datos_json' => 'array',
        'fecha_registro' => 'datetime',
    ];

    public function getDatosLimpiosAttribute()
    {
        if (!is_array($this->datos_json)) {
            return [];
        }
        return array_map(function ($val) {
            return json_decode($val) ?? trim($val ?? '', '"');
        }, $this->datos_json);
    }

    public function getDatosResueltosAttribute()
    {
        // 1. Partimos de tus datos que ya están limpios de comillas y acentos
        $datos = $this->datos_limpios;

        // 2. EL DICCIONARIO GENÉRICO: 
        // 'Llave de tu JSON' => [\App\Models\TuModelo::class, 'columna_que_quieres_mostrar']
        $mapaRelaciones = [
            'División'     => [CatalogItem::class, 'name'],
            'Departamento' => [CatalogItem::class, 'name'],
            // Puedes agregar 10 o 20 campos más aquí, el código de abajo los procesará solos.
        ];

        // 3. Mini-memoria RAM temporal para evitar saturar la base de datos
        static $memoriaLocal = [];

        foreach ($mapaRelaciones as $campo => [$claseModelo, $columnaTexto]) {
            $id = $datos[$campo] ?? null;

            // Solo procesamos si el campo existe en el JSON del usuario y es un número (ID)
            if ($id && is_numeric($id)) {
                $llaveBusqueda = "{$claseModelo}_{$id}";

                // Si es la primera vez que el sistema ve este ID (ej. División 56), va a la BD
                if (!array_key_exists($llaveBusqueda, $memoriaLocal)) {
                    $registro = $claseModelo::find($id);
                    // Lo guardamos en memoria. Si no existe, dejamos el ID para que te des cuenta.
                    $memoriaLocal[$llaveBusqueda] = $registro ? $registro->{$columnaTexto} : "ID $id (No encontrado)";
                }

                // Reemplazamos el ID por el texto real guardado en nuestra memoria rápida
                $datos[$campo] = $memoriaLocal[$llaveBusqueda];
            }
        }

        return $datos;
    }

    public function getCountProyectosAttribute()
    {
        $ciclo_id = Ciclos::select('id')->whereJsonContains('sistemas', 'investigacion')->latest()->value('id');
        if (!$ciclo_id) {
            return null;
        }

        $cantidad = implode("<br/>", AnswerFullView::where('ciclo_id', $ciclo_id)
            ->where('user_id', $this->user_id)
            ->where('section_title', 'Proyectos de Investigación')->where('pregunta', 'Folio')->distinct()->pluck('respuesta')->toArray());

        return $cantidad ?? 0;
    }
}
