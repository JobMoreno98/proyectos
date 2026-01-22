<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyRequest;
use App\Models\AnswerFullView;
use App\Models\Entry;
use App\Models\Sections;
use Illuminate\Support\Facades\Auth;

class AnswerController extends Controller
{
    public function index()
    {
        $proyectos = AnswerFullView::where('user_id', auth()->user()->id)->where('section_title', 'Proyectos de Investigación')
            ->groupBy('entry_id')->orderBy('entry_id')->get();

        return view('proyectos.index', compact('proyectos'));
    }

    public function edit($id)
    {
        $entry = Entry::with(['answers.question', 'answers'])->findOrFail($id);
        // 2. Seguridad: Verificar que el entry pertenece al usuario logueado
        if ($entry->user_id !== auth()->id()) {
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
    public function show($id)
    {
        $seccion = Sections::with('questions')->where('id', $id)->first();

        return view('respuestas.create', compact('seccion'));
    }
    public function store(StoreSurveyRequest $request)
    {
        $validated = $request->validated();

        // 1. Crear el Entry (El contenedor del envío)
        $entry = Entry::create([
            'user_id' => Auth::user()->id, // o null si es anónimo
        ]);

        // 2. Guardar las respuestas vinculadas a ese Entry
        foreach ($validated['answers'] as $questionId => $value) {
            // Lógica de archivos (igual que antes)
            if ($request->hasFile("answers.{$questionId}")) {
                $value = $request->file("answers.{$questionId}")->store('uploads', 'public');
            }

            // Usamos la relación para crear (asumiendo que definiste hasMany en Entry)
            $entry->answers()->create([
                'question_id' => $questionId,
                'value' => $value,
            ]);
        }

        return redirect()->route('proyectos.edit', $entry->id)->with('success', 'Registrado correctamente.');
        //return back()->with('success', 'Enviado correctamente');
    }
}
