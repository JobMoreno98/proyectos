@php
    // 1. BUSCAR EL VALOR DEL FOLIO
    $folioQuestion = $seccion->questions->firstWhere('label', 'Folio');
    $folioValue =
        $folioQuestion && isset($answersMap[$folioQuestion->id]) ? $answersMap[$folioQuestion->id]->value : 'S/F';
@endphp

<x-layouts::app>
    <style>
        @media print {
            @page {
                margin: 0mm;
            }

            /* 1. Forzar que el cuerpo no tenga altura predefinida ni márgenes sobrantes */
            html,
            body {
                height: auto !important;
                min-height: 100% !important;
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
                overflow: visible !important;
            }

            /* 2. Ocultar cualquier contenedor vacío de Livewire o Alpine al final del DOM */
            #page-loader,
            [x-cloak] {
                display: none !important;
            }
        }
    </style>

    <div class="max-w-4xl mx-auto pt-10 px-4 print:py-12 print:px-12">

        {{-- ENCABEZADO --}}
        <div class="bg-white shadow rounded-lg mb-6 overflow-hidden">
            <div class="bg-blue-600 px-6 py-4">
                <h1 class="text-xl font-bold text-white uppercase text-center">Información {{ $seccion->title }}</h1>
            </div>
        </div>

        @foreach ($seccion->questions as $question)
            @php
                // 1. Buscamos la respuesta del PADRE
                $answer = $answersMap[$question->id] ?? null;
                $val = $answer ? $answer->value : null;

                // 2. MAGIA: ¿Es dependiente y está vacía? Si es así, la saltamos por completo.
                $isDependent = filter_var($question->options['is_dependent'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if ($isDependent && blank($val)) {
                    continue;
                }
            @endphp

            @if ($question->type === 'sub_form')
                @php
                    // A. Configuración
                    $targetSectionId = $question->options['target_section_id'] ?? null;
                    $childEntryId = $val;

                    // B. Cargar la Sección Hija
                    $childSection = $targetSectionId
                        ? \App\Models\Sections::with(['questions' => fn($q) => $q->orderBy('sort_order')])->find(
                            $targetSectionId,
                        )
                        : null;

                    // C. Cargar Respuestas del Hijo
                    $childAnswersMap = [];
                    if ($childEntryId) {
                        $childEntry = \App\Models\Entry::with('answers')->find($childEntryId);
                        if ($childEntry) {
                            $childAnswersMap = $childEntry->answers->keyBy('question_id');
                        }
                    }
                @endphp

                @if ($childSection && $childEntryId)
                    <div class="mt-6 mb-8 border border-blue-200 rounded-lg overflow-hidden">
                        <div class="bg-blue-50 px-4 py-3 border-b border-blue-100 flex justify-between items-center">
                            <h4 class="font-bold text-blue-800 text-sm uppercase tracking-wider">
                                {{ $childSection->title }}
                            </h4>
                        </div>

                        {{-- Cuerpo del Sub-form --}}
                        <div
                            class=" space-y-5 mt-1 border border-stone-400 rounded p-2 grid grid-cols-1 md:grid-cols-2 print:grid-cols-2 gap-4 items-center content-center">

                            @foreach ($childSection->questions as $childQ)
                                @php
                                    $childAnswer = $childAnswersMap[$childQ->id] ?? null;
                                    $childVal = $childAnswer ? $childAnswer->value : null;

                                    // 3. MAGIA PARA EL HIJO: ¿Es dependiente y está vacía? La saltamos.
                                    $isChildDependent = filter_var(
                                        $childQ->options['is_dependent'] ?? false,
                                        FILTER_VALIDATE_BOOLEAN,
                                    );
                                    if ($isChildDependent && blank($childVal)) {
                                        continue;
                                    }
                                @endphp

                                <div class="pl-2">
                                    <p class="text-stone-800 font-bold mb-3 border-b-2 border-blue-500">
                                        {{ $childQ->label }}
                                    </p>

                                    <x-inputs.read-only :type="$childQ->type" :value="$childVal" :options="$childQ->options"
                                        :folio="$folioValue . ' - ' . $childQ->label" />
                                </div>
                            @endforeach

                        </div>
                    </div>
                @elseif(!$childEntryId && !$isDependent)
                    {{-- Caso: No se llenó el sub-formulario (y NO era dependiente) --}}
                    <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded text-sm text-gray-400 italic">
                        La sección "{{ $question->label }}" no fue completada.
                    </div>
                @endif
            @else
                {{-- PREGUNTAS NORMALES --}}
                <div
                    class=" md:col-span-2 space-y-5 mt-1 border border-stone-400 rounded p-2 grid grid-cols-1 md:grid-cols-2 print:grid-cols-2 gap-4 items-center content-center">
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-2">
                        {{ $question->label }}
                    </p>

                    <x-inputs.read-only :type="$question->type" :value="$val" :options="$question->options" />
                </div>
            @endif

        @endforeach
        <div class="mb-4 text-right print:hidden">
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
                Imprimir / Guardar como PDF
            </button>
        </div>
    </div>
</x-layouts::app>
