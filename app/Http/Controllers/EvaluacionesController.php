<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyRequest;
use App\Models\Answer;
use App\Models\AnswerFullView;
use App\Models\Categorias;
use App\Models\Ciclos;
use App\Models\Entry;
use App\Models\Questions;
use App\Models\Sections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

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

    public function store(StoreSurveyRequest $request)
    {
        $validated = $request->validated();

        $mainEntry = DB::transaction(function () use ($request, $validated) {
            //dd($request->section_ids);

            $idProyectos = Sections::idProyectos();

            $fecha  = date('Y-m-d');
            if ($idProyectos == (int)implode($request->section_ids)) {
                $ciclo = Ciclos::whereJsonContains(
                    'sistemas',
                    'investigacion'
                )->where('activo', true)
                    ->whereDate('inicio', '<=', $fecha)
                    ->whereDate('fin', '>=', $fecha)
                    ->latest()
                    ->first();
            } else {
                $ciclo = Ciclos::whereJsonContains(
                    'sistemas',
                    'investigacion'
                )
                    ->latest()
                    ->first();
            }


            if (!isset($ciclo->id) && !Auth::user()->hasRole('admin')) {
                //abort(403, "Te encuentras fuera del tiempo de registro.");
                Alert::info('Alto', 'Te encuentras fuera del tiempo de registro de proyetos');
                return redirect()->route('dashboard');
            }

            $entry = Entry::create([
                'user_id' => Auth::id(),
                'ciclo_id' => $ciclo->id
                // 'is_editable' => true, // Si usas este campo
            ]);

            // 2. GUARDAR RESPUESTAS NORMALES (Nivel Padre)
            if (!empty($validated['answers'])) {
                foreach ($validated['answers'] as $questionId => $value) {

                    // A. Lógica de Archivos (Padre)
                    if ($request->hasFile("answers.{$questionId}")) {
                        $value = $request->file("answers.{$questionId}")->store('uploads', 'public');
                    }

                    // B. Guardar Respuesta
                    $entry->answers()->create([
                        'question_id' => $questionId,
                        'value' => $value,
                    ]);
                }
            }

            // 3. GUARDAR SUB-FORMULARIOS (Nivel Hijo)
            // Estructura: sub_answers[ID_PREGUNTA_PADRE][ID_PREGUNTA_HIJA] = VALOR
            if (!empty($validated['sub_answers'])) {
                foreach ($validated['sub_answers'] as $parentQuestionId => $childData) {

                    // A. Crear el Entry "Hijo" (El contenedor de la sub-sección)
                    // Se crea igual que el padre, solo cambia su contenido
                    $childEntry = Entry::create([
                        'user_id' => Auth::id(),
                        'ciclo_id' => $ciclo->id
                    ]);

                    // B. VINCULAR PADRE CON HIJO
                    // En la pregunta 31 del Padre, guardamos el ID del Entry Hijo (ej: "502")
                    $entry->answers()->create([
                        'question_id' => $parentQuestionId, // Ej: 31
                        'value' => $childEntry->id,         // Aquí está la magia
                    ]);

                    // C. Guardar las Respuestas del Hijo
                    foreach ($childData as $childQuestionId => $childValue) {

                        // Lógica de Archivos (Hijo)
                        // Nota el nombre del input: sub_answers.31.26
                        if ($request->hasFile("sub_answers.{$parentQuestionId}.{$childQuestionId}")) {
                            $childValue = $request->file("sub_answers.{$parentQuestionId}.{$childQuestionId}")
                                ->store('uploads', 'public');
                        }

                        // Guardamos vinculado al ENTRY HIJO, no al padre
                        $childEntry->answers()->create([
                            'question_id' => $childQuestionId,
                            'value' => is_array($childValue) ? json_encode($childValue) : $childValue,
                        ]);
                    }
                }
            }

            return $entry;
        });

        return redirect()->route('evaluacion.edit', $mainEntry)
            ->with('success', 'Registrado correctamente.');
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
