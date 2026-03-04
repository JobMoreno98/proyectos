@props(['question', 'value', 'name' => null])

@php
    // 1. OBTENER VALORES ACTUALES
    // El value viene como array ['score' => 8, 'text' => '...']
    $currentScore = $value['score'] ?? '';
    $currentText = $value['text'] ?? '';

    // 2. PREPARAR NOMBRES DE INPUTS
    $baseName = $name ?? "answers[{$question->id}]";
    $nameScore = "{$baseName}[score]";
    $nameText = "{$baseName}[text]"; // Este nombre irá al Textarea reutilizado

    // 3. WIRE MODEL (Para el input numérico manual)
    $wireModelBase = $attributes->wire('model')->value();
    $wireScore = $wireModelBase ? "{$wireModelBase}.score" : null;

    // 4. TRUCO PARA REUTILIZAR EL TEXTAREA
    // Clonamos la pregunta para crear una "sub-pregunta" ficticia.
    // Así tu componente textarea cree que es una pregunta normal.
    $subQuestion = clone $question;
    $subQuestion->label = 'Justificación'; // Cambiamos el label interno
    $subQuestion->helper_text = null; // Quitamos el helper para no repetirlo
    $subQuestion->is_required = $question->is_required; // Mantenemos requerimiento

    // Configuración de rangos para el número
    $min = $question->options['min_score'] ?? 0;
    $max = $question->options['max_score'] ?? 10;
@endphp

<x-inputs.wrapper class="col-span-1 md:col-span-2" :label="$question->label" :name="$baseName" :required="$question->is_required"
    :helper-text="$question->helper_text">

    <div class="flex flex-col md:flex-row gap-3 items-start p-3 bg-gray-50">

        {{-- COLUMNA 1: PUNTUACIÓN (Input Manual Simple) --}}
        <div class="w-full md:w-50 flex-shrink-0 flex flex-col m-auto">
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">
                Puntuación
            </label>
            <div class="relative">
                <input type="number" name="{{ $nameScore }}" min="{{ $min }}" max="{{ $max }}"
                    step="1" 
                    class="form-input block w-full 
                    rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-center font-bold text-medium "
                    {{-- Vinculación Livewire --}} @if ($wireScore) wire:model.blur="{{ $wireScore }}" @endif
                    {{-- Valor inicial (old) --}}
                    value="{{ old(str_replace(['[', ']'], ['.', ''], $nameScore), $currentScore) }}">
                <div class="text-xs text-center text-gray-400 mt-1">
                    ({{ $min }} - {{ $max }})
                </div>
            </div>

            {{-- Error específico del puntaje --}}
            @error(str_replace(['[', ']'], ['.', ''], $nameScore))
                <span class="text-red-500 text-xs block mt-1 text-center font-medium">{{ $message }}</span>
            @enderror
        </div>

        
        <div class="flex-grow w-full md:w-50">
            <x-inputs.textarea :question="$subQuestion" :value="$currentText" :name="$nameText" />
        </div>

    </div>

</x-inputs.wrapper>
