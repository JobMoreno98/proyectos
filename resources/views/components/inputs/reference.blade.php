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
            ->whereNotIn('entry_id', $idsYaEvaluados)->orderBy('respuesta')
            ->distinct()
            ->pluck('respuesta', 'entry_id')
            ->toArray();
    }

    $isEmpty = empty($options);
    $enableSearch = !empty($options) && count($options) > 10;

    $placeholder = $isEmpty ? 'No hay datos' : 'Selecciona una opción...';

@endphp

<x-inputs.wrapper  :label="$question->label" :name="$inputName" :required="$question->is_required" :helperText="$question->helper_text" {{-- Pasamos clases extra si las hubiera --}}>
    @push('styles')
        <style>
            .ts-dropdown .option {
                white-space: normal;
                word-wrap: break-word;
                overflow-wrap: break-word;
                border-bottom: 1px solid #e5e7eb;
                /* línea gris clara */
                padding: 6px 8px;
                /* espacio interno para que se vea mejor */
            }

            .ts-dropdown .option:last-child {
                border-bottom: none;
                /* quita la línea en la última opción */
            }

            .ts-dropdown .option:hover {
                background-color: #2563eb;
                /* azul Tailwind (blue-600) */
                color: #fff;
                /* texto blanco */
                cursor: pointer;
                /* mano al pasar */
            }
        </style>
    @endpush
    {{-- ESTO ES EL SLOT: El Select real --}}
    <select id="select-{{ $question->id }}" name="{{ $inputName }}" {{-- Esto captura el wire:model del padre automáticamente --}}
        @if ($enableSearch) placeholder="Buscar..." @endif
        class=" whitespace-normal break-words form-select text-stone-900 border-gray-300 rounded-xs shadow-md focus:border-blue-500 focus:ring focus:ring-blue-200 w-full p-2"
        @if ($isEmpty) disabled @endif>
        {{-- Opción vacía / Placeholder --}}
        <option value="">{{ $placeholder . $enableSearch }}</option>

        {{-- Iteramos las opciones que sacamos de la BD --}}
        @foreach ($options as $val => $text)
            <option class="whitespace-normal break-words" value="{{ $val }}" {{-- Marcamos seleccionado si coincide con el valor guardado --}}
                @selected((string) $val === (string) $value)>
                {{ $text }}
            </option>
        @endforeach
    </select>

    @if ($enableSearch)
        <script>
            (function() {
                var selectElement = document.getElementById('select-{{ $question->id }}');
                var ts = new TomSelect("#select-{{ $question->id }}", {
                    create: false,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    },
                    onChange: function(value) {
                        // Forzamos la actualización visual y de datos
                        selectElement.value = value;
                        selectElement.setAttribute('value', value);
                        // Disparamos el mismo evento que el nativo
                        window.dispatchEvent(new CustomEvent('recalculate-code'));
                    }
                });
                // Conectamos la instancia para que system-code la lea si es necesario
                selectElement.tomselect = ts;
            })();
        </script>
    @endif
</x-inputs.wrapper>
