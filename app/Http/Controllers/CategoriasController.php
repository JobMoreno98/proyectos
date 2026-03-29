<?php

namespace App\Http\Controllers;

use App\Models\AnswerFullView;
use App\Models\Categorias;
use App\Models\Ciclos;
use App\Models\Sections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class CategoriasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Categorias $categoria)
    {

        if (!isset($categoria->titulo)) {
            abort(403, 'El registro no existe.');
        }

        if ($categoria->titulo == 'Generación y Aplicación del Conocimiento') {

            $categorias = $categoria->secciones->where('investigacion', true);
            $categoria->titulo = 'Proyectos de investigación';

            return view('categorias.index', compact('categoria'));
        } elseif ($categoria->titulo == 'ViewDatosGenerales' && Auth::user()->hasrole('admin')) {
            return redirect()->route('usuarios.index');
        }
        if ($categoria->titulo == 'Asignaciones') {
            $ciclo = Ciclos::whereJsonContains('sistemas', 'investigacion')->latest()->first();

            // 1. Calculamos los totales directo en la base de datos (Ultra rápido)
            $totalProyectos = AnswerFullView::where('ciclo_id', $ciclo->id)
                ->where('section_title', 'Proyectos de Investigación')
                ->distinct('entry_id')
                ->count('entry_id');

            $asignados = AnswerFullView::where('ciclo_id', $ciclo->id)
                ->where('section_title', 'Asignaciones')
                ->distinct('entry_id')
                ->count('entry_id');

            $porAsignar = $totalProyectos - $asignados;

            // 2. Ya no mandamos $datos. DataTables los pedirá por su cuenta.
            return view('asignaciones.index', compact('categoria', 'porAsignar'));
        }

        if ($categoria->titulo == 'Datos Generales') {

            $datos = AnswerFullView::select('entry_id')
                ->where('user_id', Auth::id()) // Es mas corto usar Auth::id()
                ->where('section_title', 'Datos Generales')
                ->first();

            if ($datos) {
                return redirect()->route('proyectos.edit', $datos->entry_id);
            } else {
                $seccion = $categoria->secciones->first();
                if ($seccion) {
                    return redirect()->route('proyectos.show', $seccion->id);
                }
            }
        }

        if ($categoria->titulo == 'Evaluaciones') {

            $ciclo =  Ciclos::whereJsonContains('sistemas', 'investigacion')->latest()->first();

            $datos = AnswerFullView::where('ciclo_id', $ciclo->id)->where('section_title', 'Asignaciones')->where('respuesta', Auth::user()->id)
                ->groupBy('entry_id')
                ->orderBy('fecha_creado')->get();

            return view('evaluaciones.index', compact('datos', 'categoria'));
        }

        return view('categorias.index', compact('categoria'));
    }

    public function datatable()
    {
        $ciclo = Ciclos::whereJsonContains('sistemas', 'investigacion')->latest()->first();

        $query = AnswerFullView::with('user')
            ->where('ciclo_id', $ciclo->id)
            ->where('section_title', 'Proyectos de Investigación')
            ->groupBy('entry_id');

        return DataTables::eloquent($query)
            ->addColumn('folio', function ($row) {
                return isset($row->data['folio']) ? $row->data['folio'] : '';
            })
            ->addColumn('nombre', function ($row) {
                if ($row->user && isset($row->user->datos_limpios)) {
                    $datos = $row->user->datos_limpios;
                    return strtoupper(($datos['Apellido Paterno'] ?? '') . ' ' . ($datos['Apellido Materno'] ?? '') . ' ' . ($datos['Nombres'] ?? ''));
                }
                return 'Sin usuario';
            })
            ->addColumn('titulo', function ($row) {
                return isset($row->data['titulo']) ? $row->data['titulo'] : '';
            })
            ->addColumn('evaluador', function ($row) {
                return isset($row->evalaudor_data['nombres'])
                    ? $row->evalaudor_data['nombres'] . ' ' . ($row->evalaudor_data['apellido-paterno'] ?? '')
                    : 'Sin evaluador';
            })
            ->addColumn('evaluacion', function ($row) {
                $calificacion = $row->obtenerCalificacionDeEvaluacion();
                $color = 'text-gray-700 bg-gray-50 ring-gray-600/20';

                if (is_numeric($calificacion)) {
                    if ($calificacion >= 80) {
                        $color = 'text-green-700 bg-green-50 ring-green-600/20';
                    } elseif ($calificacion >= 60) {
                        $color = 'text-yellow-700 bg-yellow-50 ring-yellow-600/20';
                    } else {
                        $color = 'text-red-700 bg-red-50 ring-red-600/10';
                    }
                }

                return '<div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' . $color . '">' . $calificacion . '</span></div>';
            })
            ->addColumn('acciones', function ($row) {

                return view('asignaciones.partials.acciones', ['value' => $row])->render();
            })
            ->filterColumn('nombre', function ($query, $keyword) {
                // Usamos whereExists conectando tu vista de datos generales
                $query->whereExists(function ($subquery) use ($keyword) {
                    $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('view_datos_generales')

                        // PUENTE: Conectamos el user_id de la vista de datos generales
                        // con el user_id de tu vista principal (answers_view)
                        ->whereColumn('view_datos_generales.user_id', 'answers_view.user_id')

                        // BÚSQUEDA: Agrupamos las condiciones para que busque en el nombre O en el JSON
                        ->where(function ($q) use ($keyword) {
                            $q->where('view_datos_generales.name', 'like', "%{$keyword}%")
                                // La magia del JSON_OBJECTAGG: MySQL buscará tu keyword
                                // en CUALQUIER llave del JSON (Nombres, Apellidos, etc.)
                                ->orWhere('view_datos_generales.datos_json', 'like', "%{$keyword}%");
                        });
                });
            })
            ->filterColumn('folio', function ($query, $keyword) {
                $query->whereExists(function ($subquery) use ($keyword) {
                    $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                        // 1. Le ponemos un apodo a la tabla de la subconsulta
                        ->from('answers_view as busqueda_folio')

                        // 2. Conectamos el apodo con la tabla principal (answers_view)
                        ->whereColumn('busqueda_folio.entry_id', 'answers_view.entry_id')

                        // 3. Filtramos usando el apodo
                        ->where('busqueda_folio.pregunta', 'Folio')
                        ->where('busqueda_folio.respuesta', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('titulo', function ($query, $keyword) {
                $query->whereExists(function ($subquery) use ($keyword) {
                    $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('answers_view as busqueda_titulo') // Apodo diferente
                        ->whereColumn('busqueda_titulo.entry_id', 'answers_view.entry_id')
                        ->where('busqueda_titulo.pregunta', 'Título del Proyecto')
                        ->where('busqueda_titulo.respuesta', 'like', "%{$keyword}%");
                });
            })
            ->orderColumn('folio', function ($query, $direction) {
                // Obtenemos el nombre real de la tabla/vista de tu modelo principal automáticamente
                $tablaPrincipal = $query->getModel()->getTable();

                $query->orderByRaw("(
                SELECT respuesta 
                FROM answers_view 
                WHERE answers_view.entry_id = {$tablaPrincipal}.entry_id 
                  AND answers_view.pregunta = 'Folio' 
                LIMIT 1
            ) $direction");
            })

            ->orderColumn('titulo', function ($query, $direction) {
                $tablaPrincipal = $query->getModel()->getTable();

                $query->orderByRaw("(
                SELECT respuesta 
                FROM answers_view 
                WHERE answers_view.entry_id = {$tablaPrincipal}.entry_id 
                  AND answers_view.pregunta = 'Título del Proyecto' 
                LIMIT 1
            ) $direction");
            })
            ->orderColumn('nombre', function ($query, $direction) {
                $tablaPrincipal = $query->getModel()->getTable();

                // 1. TRIM quita espacios fantasmas
                // 2. NULLIF convierte textos vacíos en verdaderos NULLs
                // 3. COALESCE usa el 'name' tradicional si el JSON no trajo apellido
                $query->orderByRaw("(
                SELECT COALESCE(
                    NULLIF(TRIM(JSON_UNQUOTE(JSON_EXTRACT(datos_json, '$.\"Apellido Paterno\"'))), ''),
                    name
                ) COLLATE utf8mb4_unicode_ci
                FROM view_datos_generales 
                WHERE view_datos_generales.user_id = {$tablaPrincipal}.user_id 
                LIMIT 1
            ) $direction");
            })
            ->rawColumns(['evaluacion', 'acciones'])
            ->toJson();
    }

    public function exportarExcel(\Illuminate\Http\Request $request)
    {


        $request->merge(['length' => -1]);

        $ciclo = \App\Models\Ciclos::whereJsonContains('sistemas', 'investigacion')->latest()->first();

        // 1. La consulta base (Igualita a la de tu datatable)
        $query = \App\Models\AnswerFullView::with('user')->where('ciclo_id', $ciclo->id)
            ->where('section_title', 'Proyectos de Investigación')
            ->groupBy('entry_id');
        // OJO: Le quitamos el orderBy('fecha_creado') aquí también

        // 2. CREAMOS EL OBJETO YAJRA (Para que aplique los filtros y ordenamientos de la URL)
        $dt = datatables()->eloquent($query)
            // Pega aquí exactamente los mismos ->filterColumn() que tienes en tu método datatable()
            ->filterColumn('nombre', function ($query, $keyword) {
                // Usamos whereExists conectando tu vista de datos generales
                $query->whereExists(function ($subquery) use ($keyword) {
                    $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('view_datos_generales')

                        // PUENTE: Conectamos el user_id de la vista de datos generales
                        // con el user_id de tu vista principal (answers_view)
                        ->whereColumn('view_datos_generales.user_id', 'answers_view.user_id')

                        // BÚSQUEDA: Agrupamos las condiciones para que busque en el nombre O en el JSON
                        ->where(function ($q) use ($keyword) {
                            $q->where('view_datos_generales.name', 'like', "%{$keyword}%")
                                // La magia del JSON_OBJECTAGG: MySQL buscará tu keyword
                                // en CUALQUIER llave del JSON (Nombres, Apellidos, etc.)
                                ->orWhere('view_datos_generales.datos_json', 'like', "%{$keyword}%");
                        });
                });
            })
            ->filterColumn('folio', function ($query, $keyword) {
                $query->whereExists(function ($subquery) use ($keyword) {
                    $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                        // 1. Le ponemos un apodo a la tabla de la subconsulta
                        ->from('answers_view as busqueda_folio')

                        // 2. Conectamos el apodo con la tabla principal (answers_view)
                        ->whereColumn('busqueda_folio.entry_id', 'answers_view.entry_id')

                        // 3. Filtramos usando el apodo
                        ->where('busqueda_folio.pregunta', 'Folio')
                        ->where('busqueda_folio.respuesta', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('titulo', function ($query, $keyword) {
                $query->whereExists(function ($subquery) use ($keyword) {
                    $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('answers_view as busqueda_titulo') // Apodo diferente
                        ->whereColumn('busqueda_titulo.entry_id', 'answers_view.entry_id')
                        ->where('busqueda_titulo.pregunta', 'Título del Proyecto')
                        ->where('busqueda_titulo.respuesta', 'like', "%{$keyword}%");
                });
            })
            // Pega aquí exactamente los mismos ->orderColumn() que tienes en tu método datatable()
            ->orderColumn('folio', function ($query, $direction) {
                // Obtenemos el nombre real de la tabla/vista de tu modelo principal automáticamente
                $tablaPrincipal = $query->getModel()->getTable();

                $query->orderByRaw("(
                SELECT respuesta 
                FROM answers_view 
                WHERE answers_view.entry_id = {$tablaPrincipal}.entry_id 
                  AND answers_view.pregunta = 'Folio' 
                LIMIT 1
            ) $direction");
            })
            ->orderColumn('titulo', function ($query, $direction) {
                $tablaPrincipal = $query->getModel()->getTable();

                $query->orderByRaw("(
                SELECT respuesta 
                FROM answers_view 
                WHERE answers_view.entry_id = {$tablaPrincipal}.entry_id 
                  AND answers_view.pregunta = 'Título del Proyecto' 
                LIMIT 1
            ) $direction");
            })
            ->orderColumn('nombre', function ($query, $direction) {
                $tablaPrincipal = $query->getModel()->getTable();

                // 1. TRIM quita espacios fantasmas
                // 2. NULLIF convierte textos vacíos en verdaderos NULLs
                // 3. COALESCE usa el 'name' tradicional si el JSON no trajo apellido
                $query->orderByRaw("(
                SELECT COALESCE(
                    NULLIF(TRIM(JSON_UNQUOTE(JSON_EXTRACT(datos_json, '$.\"Apellido Paterno\"'))), ''),
                    name
                ) COLLATE utf8mb4_unicode_ci
                FROM view_datos_generales 
                WHERE view_datos_generales.user_id = {$tablaPrincipal}.user_id 
                LIMIT 1
            ) $direction");
            });

        // 3. LA MAGIA ABSOLUTA: Extraemos la consulta ya filtrada y ordenada, pero SIN paginar
        $queryFiltrada = $dt->getFilteredQuery();

        // 4. El Streaming (Igual que antes, pero usando $queryFiltrada)
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=Asignaciones_Filtradas.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($queryFiltrada) {
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para acentos en Excel

            fputcsv($file, ['Folio', 'Nombre', 'Título', 'Evaluador', 'Calificación']);

            // Usamos el cursor sobre la consulta ya perfectamente filtrada
            foreach ($queryFiltrada->cursor() as $row) {

                $folio = $row->data['folio'] ?? '';

                $nombre = 'Sin usuario';
                if ($row->user && isset($row->user->datos_limpios)) {
                    $datos = $row->user->datos_limpios;
                    $nombre = strtoupper(($datos['Apellido Paterno'] ?? '') . ' ' . ($datos['Apellido Materno'] ?? '') . ' ' . ($datos['Nombres'] ?? ''));
                }

                $titulo = $row->data['titulo'] ?? '';

                $evaluador = isset($row->evalaudor_data['nombres'])
                    ? $row->evalaudor_data['nombres'] . ' ' . ($row->evalaudor_data['apellido-paterno'] ?? '')
                    : 'Sin evaluador';

                $calificacion = $row->obtenerCalificacionDeEvaluacion();

                fputcsv($file, [$folio, $nombre, $titulo, $evaluador, $calificacion]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
