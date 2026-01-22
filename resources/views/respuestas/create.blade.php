@php
    $titlePage = 'Crear - ' . $seccion->title;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            {{ $titlePage }}
        </h2>
    </x-slot>
    <div class="container m-auto">
        <div class="max-w-3xl mx-auto py-10 px-4">

            {{-- Muestra mensaje de éxito si existe --}}
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    {{ session('success') }}
                </div>
            @endif
            {{--  
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
--}}
            {{-- IMPORTANTE: enctype es necesario para subir archivos --}}
            <form action="{{ route('proyectos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                @csrf
                <div class="bg-white shadow rounded-lg p-6 border-t-2 border-blue-500">
                    {{-- 1. Iteramos sobre las SECCIONES --}}

                    <input type="hidden" name="section_ids[]" value="{{ $seccion->id }}">

                    <h3 class="font-semibold text-gray-800">{{ $seccion->title }}</h3>
                    @if ($seccion->description)
                        <p class="text-gray-500 text-sm mb-4">{{ $seccion->description }}</p>
                    @endif
                    <div class="space-y-5 mt-4">
                        @foreach ($seccion->questions as $question)
                            @php
                                $fieldName = "answers[{$question->id}]";
                                $errorKey = "answers.{$question->id}";
                                $savedValue = $existingAnswers[$question->id] ?? null;
                                $defaultValue = $question->options['default_value'] ?? '';
                                $finalValue = old($errorKey, $savedValue ?? $defaultValue);
                            @endphp

                            <div class="flex flex-col">
                                @php
                                    // Mapeo de seguridad: Si el tipo no tiene componente, usa 'text' por defecto
                                    $componentName = 'inputs.' . $question->type;
                                    if (!view()->exists("components.{$componentName}")) {
                                        $componentName = 'inputs.text';
                                    }
                                @endphp
                                <x-dynamic-component :component="$componentName" :question="$question" :value="$finalValue" />
                                @switch($question->type)
                                    @case('sub_form')
                                        @php
                                            // 1. Identificamos qué sección incrustar
                                            $targetSectionId = $question->options['target_section_id'];

                                            // 2. Buscamos las preguntas de esa sección (Mejor si las pasas desde el controlador para optimizar)
                                            $childSection = \App\Models\Sections::with('questions')->find(
                                                $targetSectionId,
                                            );

                                            // 3. Obtenemos si ya hay un Entry guardado (El valor de la respuesta es el ID del entry hijo)
                                            $childEntryId = $existingAnswers[$question->id] ?? null;

                                            // 4. Si hay entry hijo, cargamos sus respuestas
                                            $childAnswers = [];
                                            if ($childEntryId) {
                                                $childEntry = \App\Models\Entry::with('answers')->find($childEntryId);
                                                // Mapeamos [question_id => value]
                                                $childAnswers = $childEntry->answers
                                                    ->pluck('value', 'question_id')
                                                    ->toArray();
                                            }
                                        @endphp

                                        @if ($childSection)
                                            <div class="border-l-4 border-blue-500 pl-4 ml-2 my-4 bg-gray-50 p-4 rounded">
                                                <h4 class="text-blue-800 font-bold mb-3">{{ $childSection->title }}
                                                </h4>

                                                {{-- Iteramos las preguntas de la sección HIJA --}}
                                                @foreach ($childSection->questions as $childQ)
                                                    @php
                                                        // NAMING CRÍTICO: sub_answers[PADRE][HIJO]
                                                        $childInputName = "sub_answers[{$question->id}][{$childQ->id}]";

                                                        // Recuperamos valor: old > guardado > default
                                                        $childValue = old(
                                                            "sub_answers.{$question->id}.{$childQ->id}",
                                                            $childAnswers[$childQ->id] ?? '',
                                                        );
                                                    @endphp

                                                    <div class="mb-3">
                                                        <label class="block text-sm text-gray-600">{{ $childQ->label }}</label>

                                                        {{-- Renderizado simplificado (copia tu switch grande aquí) --}}
                                                        @if ($childQ->type === 'text')
                                                            <input type="text" name="{{ $childInputName }}"
                                                                value="{{ $childValue }}" class="form-input w-full">
                                                        @elseif($childQ->type === 'select')
                                                            {{-- ... tu lógica de select ... --}}
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @break
                                @endswitch
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-center mt-4">
                        <button type="submit"
                            class="bg-blue-600 text-xs hover:bg-blue-700 text-white font-bold py-1 px-4 rounded shadow-lg transition duration-150">
                            Guardar
                        </button>
                    </div>
            </form>
        </div>
</x-app-layout>
