@props(['question', 'value'])

@php
    $name = "answers[{$question->id}]";
    $errorKey = "answers.{$question->id}";
@endphp

<x-inputs.wrapper :label="$question->label" :name="$errorKey" :required="$question->is_required">
    <input type="number" name="{{ $name }}" id="{{ $errorKey }}"
        value="{{ $value }}"step="any"
        class="p-2 text-stone-900 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 w-full">
</x-inputs.wrapper>
