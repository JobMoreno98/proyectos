<?php

namespace App\Http\Controllers;

use App\Models\AnswerFullView;
use App\Models\Categorias;
use App\Models\Entry;
use App\Models\Sections;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function datos_generales()
    {

        $categoria = Categorias::with('secciones')->where('titulo', 'Datos Generales')->first();

        $datos = AnswerFullView::select('entry_id')
            ->where('user_id', Auth::id()) // Es mas corto usar Auth::id()
            ->where('section_title', 'Datos Generales')
            ->first();

        if (isset($datos->entry_id)) {
            return redirect()->route('proyectos.edit', $datos->entry_id);
        } else {
            $seccion = $categoria->secciones->first();

            if ($seccion) {
                return redirect()->route('proyectos.show', $seccion->id);
            }
        }

        $entry = Entry::with(['answers.question', 'answers'])->findOrFail($datos->entry_id);

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
}
