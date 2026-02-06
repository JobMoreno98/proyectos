<?php

namespace App\Http\Controllers;

use App\Models\AnswerFullView;
use App\Models\Categorias;
use App\Models\Ciclos;
use App\Models\Sections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        }
        if ($categoria->titulo == 'Asignaciones') {
            $ciclo =  Ciclos::whereJsonContains('sistemas', 'investigacion')->where('activo', true)->latest()->first();
            $query = AnswerFullView::where('ciclo_id', $ciclo->id)
                ->groupBy('entry_id')
                ->orderBy('fecha_creado');

            $datos = (clone $query)->where('section_title', 'Proyectos de Investigación')->get();

            $asignados = (clone $query)->where('section_title', 'Asignaciones')->distinct('entry_id')->get()->count();

            $porAsignar = $datos->count() - $asignados;

            return view('asignaciones.index', compact('datos', 'categoria', 'porAsignar'));
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
        if ($categoria->titulo == 'Evaluaciones' && Auth::user()->hasRole('admin')) {
            $ciclo =  Ciclos::whereJsonContains('sistemas', 'investigacion')->where('activo', true)->latest()->first();
            $query = AnswerFullView::where('ciclo_id', $ciclo->id)
                ->groupBy('entry_id')
                ->orderBy('fecha_creado');

            $datos = (clone $query)->where('section_title', 'Evaluaciones')->get();

            $asignados = (clone $query)->where('section_title', 'Evaluaciones')->distinct('entry_id')->get()->count();

            $porAsignar = $datos->count() - $asignados;

            return view('asignaciones.index', compact('datos', 'categoria', 'porAsignar'));
        }
        
        if ($categoria->titulo == 'Evaluaciones') {
            
            $ciclo =  Ciclos::whereJsonContains('sistemas', 'investigacion')->where('activo', true)->latest()->first();

            $datos = AnswerFullView::where('ciclo_id', $ciclo->id)->where('section_title', 'Asignaciones')->where('respuesta', Auth::user()->id)
                ->groupBy('entry_id')
                ->orderBy('fecha_creado')->get();

          //dd($datos);

            return view('evaluaciones.index', compact('datos', 'categoria'));
        }

        return view('categorias.index', compact('categoria'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Categorias $categorias)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categorias $categorias)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Categorias $categorias)
    {
        //
    }
}
