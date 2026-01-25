<?php

namespace App\Http\Controllers;

use App\Models\AnswerFullView;
use App\Models\Categorias;
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

        if ($categoria->titulo == 'Datos Generales') {
            $datos = AnswerFullView::select('entry_id')
                ->where('user_id', Auth::id()) // Es mas corto usar Auth::id()
                ->where('section_title', 'Datos Generales')
                ->first();

            if ($datos) {
                return redirect()->route('answers.edit', $datos->entry_id);
            } else {
                $seccion = $categoria->secciones->first();
                
                if ($seccion) {
                    return redirect()->route('answers.show', $seccion->id);
                }
            }
        }

        if ($categoria->titulo == 'Datos Laborales') {
            $datos = AnswerFullView::select('entry_id')
                ->where('user_id', Auth::id()) // Es mas corto usar Auth::id()
                ->where('section_title', 'Datos Laborales')
                ->first();
            if ($datos) {
                return redirect()->route('answers.edit', $datos->entry_id);
            } else {
                $seccion = $categoria->secciones->first();

                if ($seccion) {
                    return redirect()->route('answers.show', $seccion->id);
                }
            }
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
