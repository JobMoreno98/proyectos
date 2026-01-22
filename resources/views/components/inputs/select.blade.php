@props(['question', 'value', 'type' => 'select'])

@php
    $name = "answers[{$question->id}]";
    $errorKey = "answers.{$question->id}";
    $enableSearch = count($question->options['choices']) > 10;
@endphp

<x-inputs.wrapper :label="$question->label" :name="$errorKey" :required="$question->is_required">
    <div wire:ignore>
        <select name="{{ $name }}" id="select-{{ $question->id }}"
            @if ($enableSearch) placeholder="Buscar..." @endif
            class="form-select text-stone-900 border-gray-300 rounded-xs shadow-md focus:border-blue-500 focus:ring focus:ring-blue-200 w-full p-2">
            <option value="">Seleccione...</option>

            {{-- Verifica que exista y sea iterable --}}
            @if (!empty($question->options['choices']))
                @foreach ($question->options['choices'] as $choice)
                    {{-- Ahora accedemos como array: $choice['value'] y $choice['label'] --}}
                    <option value="{{ $choice['value'] }}"
                        {{ old($errorKey, $value) == $choice['value'] ? 'selected' : '' }}>
                        {{ $choice['label'] }}
                    </option>
                @endforeach
            @endif
        </select>
    </div>
    @if ($enableSearch)
        <script>
            new TomSelect("#select-{{ $question->id }}", {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        </script>
    @endif
</x-inputs.wrapper>
