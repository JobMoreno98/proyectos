@props(['question', 'value', 'type' => 'date'])

@php
    $name = "answers[{$question->id}]";
    $errorKey = "answers.{$question->id}";
@endphp

<x-inputs.wrapper :label="$question->label" :name="$errorKey" :required="$question->is_required">
    <input 
        type="{{ $type }}" 
        name="{{ $name }}" 
        id="{{ $errorKey }}"
        value="{{ $value }}"
        class="p-2 form-input w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        {{ $attributes }} 
    >
</x-inputs.wrapper>