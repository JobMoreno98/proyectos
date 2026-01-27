@props(['question', 'value' => '', 'name'])

@php

    $options = [];
    $sourceQuestionId = $question->options['source_question_id'] ?? null;
    $inputName = $name ?? "answers[{$question->id}]";

    // Solo buscamos si hay una pregunta origen configurada
    if ($sourceQuestionId) {
        // Usamos el modelo Answer directamente
        $idsYaEvaluados = \App\Models\AnswerFullView::query()
            ->where('section_title', 'Evaluaciones')
            ->where('pregunta', 'Proyecto') // <--- OJO: Asegúrate que este sea el nombre exacto de la pregunta en la Evaluación
            ->pluck('respuesta')
            ->filter() // Quitamos nulos o vacíos
            ->toArray();    

        // PASO 2: Tu consulta original filtrada
        $options = \App\Models\AnswerFullView::query()
            ->where('question_id', $sourceQuestionId)
            // ->where('section_title', '!=', ...) // Ya no es tan necesario si filtras por question_id, pero mal no hace.

            // AQUÍ ESTÁ LA CLAVE: Excluir los que ya encontramos
            ->whereNotIn('entry_id', $idsYaEvaluados)

            ->distinct()
            ->pluck('respuesta', 'entry_id')
            ->toArray();
    }

    $isEmpty = empty($options);
    $enableSearch = !empty($options) && count($options) > 10;

    $placeholder = $isEmpty ? 'No hay datos' : 'Selecciona una opción...';

@endphp

{{-- 
    3. RENDERIZADO
    Usamos tu componente Wrapper (ui.wrapper) para mantener el diseño consistente.
    Le pasamos los props que tu wrapper espera.
--}}
<x-inputs.wrapper :label="$question->label" :name="$inputName" :required="$question->is_required" :helperText="$question->helper_text" {{-- Pasamos clases extra si las hubiera --}}>

    {{-- ESTO ES EL SLOT: El Select real --}}
    <select id="{{ $inputName }}" name="{{ $inputName }}" {{-- Esto captura el wire:model del padre automáticamente --}}
        @if ($enableSearch) placeholder="Buscar..." @endif
        class="form-select text-stone-900 border-gray-300 rounded-xs shadow-md focus:border-blue-500 focus:ring focus:ring-blue-200 w-full p-2"
        @if ($isEmpty) disabled @endif>
        {{-- Opción vacía / Placeholder --}}
        <option value="">{{ $placeholder }}</option>

        {{-- Iteramos las opciones que sacamos de la BD --}}
        @foreach ($options as $val => $text)
            <option value="{{ $val }}" {{-- Marcamos seleccionado si coincide con el valor guardado --}} @selected((string) $val === (string) $value)>
                {{ $text }}
            </option>
        @endforeach
    </select>

</x-inputs.wrapper>
