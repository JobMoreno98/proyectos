@props(['question', 'value' => '', 'name'])

@php

    $options = [];
    $sourceQuestionId = $question->options['source_question_id'] ?? null;
    $inputName = $name ?? "answers[{$question->id}]";
    //dd($value);

    if ($sourceQuestionId) {
        // Usamos el modelo Answer directamente
        $idsYaEvaluados = \App\Models\AnswerFullView::query()
            ->where('section_title', 'Asignaciones')
            ->where('respuesta', '!=', $value)
            ->where('pregunta', 'Proyecto') 
            ->pluck('respuesta')
            ->filter() // Quitamos nulos o vacíos
            ->toArray();
        
        $options = \App\Models\AnswerFullView::query()
            ->where('question_id', $sourceQuestionId)
            ->whereNotIn('entry_id', $idsYaEvaluados)
            ->distinct()
            ->pluck('respuesta', 'entry_id')
            ->toArray();
    }

    $isEmpty = empty($options);
    $enableSearch = !empty($options) && count($options) > 10;

    $placeholder = $isEmpty ? 'No hay datos' : 'Selecciona una opción...';

@endphp
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
