<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyRequest;
use App\Models\Answer;
use App\Models\AnswerFullView;
use App\Models\Categorias;
use App\Models\Entry;
use App\Models\Questions;
use App\Models\Sections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EvaluacionesController extends Controller
{
    public function create($id)

    {

        $tipo = AnswerFullView::where('entry_id', $id)->where('question_id', Questions::idTipo())->value('respuesta');
        if (strcmp($tipo, "N") == 0) {

            $idSeccionEvaluacion = Sections::idEvaluacionNuevo();
        } else {
            $idSeccionEvaluacion = Sections::idEvaluacionContinuacion();
        }

        $seccion = Sections::with(['questions' => function ($q) {
            $q->orderBy('sort_order'); // Asegurar orden correcto
        }])->where('id', $idSeccionEvaluacion)->orderBy('sort_order')->first();

        $proyectoID = $id;

        return view('evaluaciones.create', compact('seccion', 'proyectoID'));

        foreach ($proyectoAsignado->answers as $key => $value) {
            echo dd($value->question->section->questions[16]) . "<br/>";
        }

        $seccion = Sections::with('questions')->where('id', $idSeccionEvaluacion)->first();
    }

    public function show($id)
    {

        $entry = Entry::with(['user', 'answers'])->findOrFail($id);
        $idSecion = ($entry->answers->first()->question->section_id);

        $seccion = Sections::with(['questions' => function ($q) {
            $q->orderBy('sort_order'); // Asegurar orden correcto
        }])->where('id', $idSecion)->orderBy('sort_order')->first();

        $answersMap = $entry->answers->keyBy('question_id');


        return view('entries.show', compact('entry', 'seccion', 'answersMap'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($evaluacionID)
    {
        $entry = Entry::with(['answers.question', 'answers'])->findOrFail($evaluacionID);
        //dd($entry, Auth::user()->id);

        // 2. Seguridad: Verificar que el entry pertenece al usuario logueado+
        if ((($entry->user_id !== Auth::user()->id) && (Auth::user()->hasRole('admin')))) {
            abort(403, 'No tienes permiso para editar este registro.');
        }

        if (! $entry->is_editable) {
            return redirect()->route('dashboard')
                ->with('error', 'Este formulario ya fue enviado y no puede ser modificado.');
        }

        // 3. Identificar la Sección
        // Asumimos que todas las respuestas de un entry son de la misma sección.
        // Tomamos la sección de la primera respuesta encontrada.

        $firstAnswer = $entry->answers->first();
        $sectionId = $firstAnswer ? $firstAnswer->question->section_id : null;

        if (! $sectionId) {
            return back()->with('error', 'Registro corrupto o vacío');
        }

        // 4. Cargar la estructura del formulario (Preguntas)
        $seccion = Sections::where('id', $sectionId)
            ->with(['questions' => fn($q) => $q->orderBy('sort_order')])
            ->first();

        // 5. TRUCO PRO: Mapear respuestas para acceso rápido en la vista
        // Resultado: [ ID_PREGUNTA => 'Valor de la respuesta' ]
        $existingAnswers = $entry->answers->pluck('value', 'question_id')->toArray();

        return view('evaluaciones.edit', compact('entry', 'seccion', 'existingAnswers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreSurveyRequest $request, string $id)
    {
        $entry = Entry::findOrFail($id);

        if ($entry->user_id !== Auth::user()->id) {
            abort(403, 'No tienes permiso para editar este registro.');
        }
        if (! $entry->is_editable) {
            return redirect()->route('dashboard')
                ->with('error', 'Este formulario ya fue enviado y no puede ser modificado.');
        }

        $validated = $request->validated();
        $ciclo = $entry->ciclo_id;

        DB::transaction(function () use ($request, $validated, $entry, $ciclo) {

            // ... (PARTE 1: RESPUESTAS PADRE - SE MANTIENE IGUAL) ...
            if (!empty($validated['answers'])) {
                foreach ($validated['answers'] as $questionId => $value) {
                    // ... Lógica de archivos y guardado normal ...
                    if ($request->hasFile("answers.{$questionId}")) {
                        $value = $request->file("answers.{$questionId}")->store('uploads', 'public');
                    } elseif ($value === null) {
                        $q = \App\Models\Questions::find($questionId);
                        if ($q && $q->type === 'file' && $entry->answers()->where('question_id', $questionId)->exists()) {
                            continue;
                        }
                    }

                    Answer::updateOrCreate(
                        ['entry_id' => $entry->id, 'question_id' => $questionId],
                        ['value' => $value]
                    );
                }
            }

            // =========================================================
            // PARTE 2: SUB-FORMULARIOS (CORREGIDO PARA TU TABLA ENTRIES)
            // =========================================================
            if (!empty($validated['sub_answers'])) {

                foreach ($validated['sub_answers'] as $parentQId => $incomingChildData) {

                    // 1. Obtenemos la Pregunta Padre para saber la SECCIÓN DESTINO
                    // Como 'entries' no guarda la sección, necesitamos sacarla de aquí.
                    $parentQuestion = \App\Models\Questions::find($parentQId);
                    $targetSectionId = $parentQuestion->options['target_section_id'] ?? null;

                    if (!$targetSectionId) continue; // Si no está configurada, saltamos

                    // 2. Buscamos o Creamos el Entry Hijo
                    $linkAnswer = Answer::where('entry_id', $entry->id)
                        ->where('question_id', $parentQId)
                        ->first();

                    $childEntry = $linkAnswer ? Entry::find($linkAnswer->value) : null;

                    if (!$childEntry) {
                        // CREACIÓN: Solo ID y User_ID
                        $childEntry = Entry::create([
                            'user_id' => Auth::id(),
                            'ciclo_id' =>  $ciclo
                            // 'section_id' => ... ELIMINADO (No existe en tu tabla)
                            // 'status' => ... ELIMINADO
                        ]);

                        // Vinculamos al padre
                        Answer::updateOrCreate(
                            ['entry_id' => $entry->id, 'question_id' => $parentQId],
                            ['value' => $childEntry->id]
                        );
                    }

                    // 3. GUARDADO DE RESPUESTAS (Iterando sobre el Schema, no el Input)
                    if ($childEntry) {

                        // CORRECCIÓN: Usamos $targetSectionId que obtuvimos arriba
                        // en lugar de $childEntry->section_id
                        $childQuestions = \App\Models\Questions::where('section_id', $targetSectionId)->get();

                        foreach ($childQuestions as $question) {
                            $childQId = $question->id;
                            $value = $incomingChildData[$childQId] ?? null;

                            // A. Repeater vacío -> Array vacío
                            if ($value === null && in_array($question->type, ['repeater', 'repeater_awards'])) {
                                $value = [];
                            }

                            // B. Archivos
                            if ($request->hasFile("sub_answers.{$parentQId}.{$childQId}")) {
                                $value = $request->file("sub_answers.{$parentQId}.{$childQId}")->store('uploads', 'public');
                            } elseif ($question->type === 'file' && $value === null) {
                                if ($childEntry->answers()->where('question_id', $childQId)->exists()) {
                                    continue;
                                }
                            }

                            // C. Guardar
                            Answer::updateOrCreate(
                                ['entry_id' => $childEntry->id, 'question_id' => $childQId],
                                ['value' => is_array($value) ? json_encode($value) : $value]
                            );
                        }
                    }
                }
            }
        });

        return redirect()->route('evaluacion.edit', $id)->with('success', 'Actualizado correctamente.');
    }
}
