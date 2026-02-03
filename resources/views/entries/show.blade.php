@php
    // 1. BUSCAR EL VALOR DEL FOLIO (Solo una vez, antes del bucle)
    // Buscamos en la colección de preguntas la que se llame "Folio"
    $folioQuestion = $seccion->questions->firstWhere('label', 'Folio');

    // Si existe, buscamos su respuesta en el mapa de respuestas
    $folioValue = $folioQuestion && isset($answersMap[$folioQuestion->id]) ? $answersMap[$folioQuestion->id]->value : 'S/F'; // 'S/F' = Sin Folio (por si acaso no existe)

@endphp

<x-layouts::app>
    <div class="max-w-4xl mx-auto py-10 px-4">

        {{-- ENCABEZADO: Título de la Sección y Datos del Entry --}}
        <div class="bg-white shadow rounded-lg mb-6 overflow-hidden">
            <div class="bg-blue-600 px-6 py-4">
                <h1 class="text-xl font-bold text-white uppercase text-center">Información {{ $seccion->title }}</h1>
                {{-- 
                <p class="text-blue-100 text-sm">Respuesta #{{ $entry->id }} |
                    {{ $entry->created_at->format('d/m/Y H:i') }}</p>
                      --}}
            </div>
        </div>
        @foreach ($seccion->questions as $question)

            @php
                // 1. Buscamos la respuesta del PADRE
                $answer = $answersMap[$question->id] ?? null;
                $val = $answer ? $answer->value : null;
                //dd($val);
            @endphp
            {{ $question->type }}
            @if ($question->type === 'sub_form')
                @php
                    // A. Configuración
                    $targetSectionId = $question->options['target_section_id'] ?? null;
                    $childEntryId = $val; // En un sub_form, el "valor" es el ID del entry hijo

                    // B. Cargar la Sección Hija (Solo si tenemos ID)
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
                            // Mapeamos igual que en el padre para acceso rápido
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
                            class="col-span-2 space-y-5 mt-1 border border-stone-400 rounded p-2  grid grid-cols-1 md:grid-cols-2 gap-4 items-center content-center">
                            @foreach ($childSection->questions as $childQ)
                                @php
                                    $childAnswer = $childAnswersMap[$childQ->id] ?? null;
                                    $childVal = $childAnswer ? $childAnswer->value : null;
                                @endphp

                                <div class="pl-2">
                                    <p class="col-span-2 text-stone-800 font-bold mb-3 border-b-2 border-blue-500">
                                        {{ $childQ->label }}
                                    </p>

                                    <x-inputs.read-only :type="$childQ->type" :value="$childVal" :options="$childQ->options"
                                        :folio="$folioValue . ' - ' . $childQ->label" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif(!$childEntryId)
                    {{-- Caso: No se llenó el sub-formulario --}}
                    <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded text-sm text-gray-400 italic">
                        La sección "{{ $question->label }}" no fue completada.
                    </div>
                @endif
            @else
                <div
                    class="col-span-2 space-y-5 mt-1 border border-stone-400 rounded p-2  grid grid-cols-1 md:grid-cols-2 gap-4 items-center content-center">

                    <p class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-2">
                        {{ $question->label }}
                    </p>

                    <x-inputs.read-only :type="$question->type" :value="$val" :options="$question->options" />
                </div>
            @endif

        @endforeach


    </div>
</x-layouts::app>
