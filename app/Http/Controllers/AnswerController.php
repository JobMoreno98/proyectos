<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyRequest;
use App\Models\Answer;
use App\Models\AnswerFullView;
use App\Models\Entry;
use App\Models\Sections;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnswerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $proyectos = AnswerFullView::where('user_id', Auth::user()->id)->where('section_title', 'Proyectos de Investigación')
            ->groupBy('entry_id')
            ->orderBy('fecha_creado')
            ->get();
        
        return view('respuestas.index', compact('proyectos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($id)
    {
        $seccion = Sections::with('questions')->where('id', $id)->first();

        return view('respuestas.create', compact('seccion'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSurveyRequest $request)
    {
        $validated = $request->validated();

        // Usamos DB::transaction para seguridad (evita datos huérfanos si algo falla)
        $mainEntry = DB::transaction(function () use ($request, $validated) {

            // 1. CREAR EL ENTRY PRINCIPAL (PADRE)
            $entry = Entry::create([
                'user_id' => Auth::id(),
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

            return $entry; // Retornamos el entry para usarlo en el redirect
        });

        return redirect()->route('proyectos.edit', $mainEntry->id)
            ->with('success', 'Registrado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {

        $seccion = Sections::with('questions')->where('id', $id)->first();
        return view('respuestas.create', compact('seccion'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {

        $entry = Entry::with(['answers.question', 'answers'])->findOrFail($id);

        // 2. Seguridad: Verificar que el entry pertenece al usuario logueado
        if ($entry->user_id !== Auth::user()->id) {
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

        return view('respuestas.edit', compact('entry', 'seccion', 'existingAnswers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreSurveyRequest $request, $id)
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

        DB::transaction(function () use ($request, $validated, $entry) {

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

        return redirect()->route('proyectos.edit', $entry->id)->with('success', 'Actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $entry = Entry::findOrFail($id);
        if ($entry->user_id !== Auth::user()->id) {
            abort(403, 'No tienes permiso para editar este registro.');
        }
        if (! $entry->is_editable) {
            return redirect()->route('dashboard')
                ->with('error', 'Este formulario ya fue enviado y no puede ser modificado.');
        }
        $entry->delete();
        return redirect()->route('proyectos.index');
    }
}
