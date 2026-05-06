<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyRequest;
use App\Models\Answer;
use App\Models\AnswerFullView;
use App\Models\Ciclos;
use App\Models\Entry;
use App\Models\Sections;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

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

        return redirect()->route('proyectos.edit', $mainEntry)
            ->with('success', 'Registrado correctamente.');
    }

    public function show($id)
    {
        $seccion = Sections::with('questions')->where('id', $id)->first();
        if ($seccion->title == 'Proyectos de Investigación') {
            $fecha  = date('Y-m-d');
            $ciclo = Ciclos::whereJsonContains(
                'sistemas',
                'investigacion'
            )->where('activo', true)
                ->whereDate('inicio', '<=', $fecha)
                ->whereDate('fin', '>=', $fecha)
                ->latest()
                ->first();
            if (!isset($ciclo->id)) {
                //abort(403, "Te encuentras fuera del tiempo de registro.");
                Alert::info('Alto', 'Te encuentras fuera del tiempo de registro de proyetos');
                return redirect()->route('dashboard');
            }
        }


        return view('respuestas.create', compact('seccion'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {

        $entry = Entry::with(['answers.question', 'answers'])->findOrFail($id);
        //dd($entry, Auth::user()->id);
        //dd(Auth::user()->hasRole('admin'));
        // 2. Seguridad: Verificar que el entry pertenece al usuario logueado+
        if ((($entry->user_id !== Auth::user()->id) && (!Auth::user()->hasRole('admin')))) {
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

        if ((($entry->user_id !== Auth::user()->id) && (!Auth::user()->hasRole('admin')))) {
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

        return redirect()->route('proyectos.edit', $entry->id)->with('success', 'Actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $entry = Entry::findOrFail($id);


        if ($entry->user_id !== Auth::user()->id && !Auth::user()->hasRole('admin')) {
            abort(403, 'No tienes permiso para editar este registro.');
        }
        //dd(!$entry->is_editable && !Auth::user()->hasRole('admin'));
        if (! $entry->is_editable && !Auth::user()->hasRole('admin')) {
            return redirect()->route('dashboard')
                ->with('error', 'Este formulario ya fue enviado y no puede ser modificado.');
        }

        $entry->delete();


        return redirect()->route('dashboard')->with('success', 'Este formulario se ha eliminado de forma exitosa.');
    }

    public function asignar($id)
    {
        $asigar = Sections::idAsignaciones();
        $seccion = Sections::with('questions')->where('id', $asigar)->first();
        // dd($seccion);

        $proyectoID = $id;

        return view('asignaciones.create', compact('seccion', 'proyectoID'));

    }

    public function definitivo($id)
    {
        $entry = Entry::findOrFail($id);

        if ($entry->user_id !== Auth::user()->id && !Auth::user()->hasRole('admin')) {
            abort(403, 'No tienes permiso para editar este registro.');
        }
        $entry->is_editable = $entry->is_editable ? 0 : 1;
        $entry->update();
        return redirect()->route('dashboard')
            ->with('success', 'Este formulario se ha entregado de forma exitosa.');
    }

    public function imprimir($id)
    {
        // 1. Cargamos los datos exactamente igual que en tu vista web
        $entry = Entry::with('answers')->findOrFail($id);

        if ($entry->user_id !== Auth::user()->id) {
            abort(403, 'No tienes permiso para imprimir este registro.');
        }
        $idSecion = ($entry->answers->first()->question->section_id);

        $seccion = \App\Models\Sections::with(['questions' => function ($q) {
            $q->orderBy('sort_order'); // Asegurar orden correcto
        }])->where('id', $idSecion)->orderBy('sort_order')->first();

        $answersMap = $entry->answers->keyBy('question_id');

        // 2. Generamos el PDF apuntando a una vista nueva y exclusiva para impresión
        $pdf = Pdf::loadView('entries.pdf', [
            'entry' => $entry,
            'seccion' => $seccion,
            'answersMap' => $answersMap,
        ]);

        // 3. Devolvemos el archivo para que el navegador lo descargue
        return $pdf->stream('formulario_' . $entry->id . '.pdf');
    }
}
