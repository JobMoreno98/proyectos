<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\AnswerFullView;
use App\Models\Categorias;
use App\Models\Entry;
use App\Models\Sections;
use Illuminate\Http\Request;

class EvaluacionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // 1. Cargar el Entry con su Usuario y sus Respuestas
        $entry = Entry::with(['user', 'answers'])->findOrFail($id);
        $idSecion = ($entry->answers->first()->question->section_id);

        // 2. Cargar la estructura del Formulario (Secciones -> Preguntas)
        // Asumo que tienes un modelo Sections. Si hay múltiples formularios, filtra aquí.
        $seccion = \App\Models\Sections::with(['questions' => function ($q) {
            $q->orderBy('sort_order'); // Asegurar orden correcto
        }])->where('id', $idSecion)->orderBy('sort_order')->first();

        // 3. TRUCO DE RENDIMIENTO:
        // Mapeamos las respuestas usando el ID de la pregunta como llave.
        // Estructura resultante: [ 105 => ObjetoRespuesta, 106 => ObjetoRespuesta ]
        $answersMap = $entry->answers->keyBy('question_id');

        return view('entries.show', compact('entry', 'seccion', 'answersMap'));

        $idSeccionEvaluacion = Sections::idEvaluacion();


        $entry = Entry::with('proyecto')->findOrFail($id);

        $proyectoAsignado = Answer::where('value', $entry->proyecto->value)
            ->where('question_id', $entry->proyecto->question->options['source_question_id'])->first();

        $proyectoAsignado = Entry::with(['answers.question', 'answers'])->findOrFail($proyectoAsignado->entry_id);

        foreach ($proyectoAsignado->answers as $key => $value) {
            dd($value->entry_id, $value->question->section_id);
            echo dd($value->question->section->questions[16]) . "<br/>";
        }

        $seccion = Sections::with('questions')->where('id', $idSeccionEvaluacion)->first();

        return view('respuestas.create', compact('seccion'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
