@php
    $titlePage = 'Crear - ' . $seccion->title;
@endphp

<x-layouts::app :title="$titlePage">
    <div class="container m-auto">
        <div class="max-w-7xl mx-auto py-10 px-4">

            {{-- Muestra mensaje de éxito si existe --}}
            @if (session('success'))
                <x-alert type="success">
                    {{ session('success') }}
                </x-alert>
            @endif
            

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
 
            {{-- IMPORTANTE: enctype es necesario para subir archivos --}}
            <form action="{{ route('proyectos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                @csrf
                <div class="bg-white shadow rounded-lg p-6 border-t-2 border-blue-500">

                    <input type="hidden" name="section_ids[]" value="{{ $seccion->id }}">

                    <input type="hidden" name="categoria_id" value="{{ $seccion->categoria_id }}">

                    <input type="hidden" name="proyecto" value="{{ $proyectoID }}">

                    <h3 class="font-semibold text-gray-800">{{ $seccion->title }}</h3>
                    @if ($seccion->description)
                        <p class="text-gray-500 text-sm mb-4">{{ $seccion->description }}</p>
                    @endif

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 ">
                        @foreach ($seccion->questions as $question)
                            @php
                                $fieldName = "answers[{$question->id}]";
                                $errorKey = "answers.{$question->id}";
                                $savedValue = $existingAnswers[$question->id] ?? null;
                                $defaultValue = $question->options['default_value'] ?? '';
                                // 1. Identificamos si esta es la pregunta que queremos ocultar y pre-llenar
                                $esPreguntaProyecto = strtolower(trim($question->label)) === 'proyecto';

                                if ($esPreguntaProyecto) {
                                    $defaultValue = $proyectoID;
                                }

                                $finalValue = old($errorKey, $savedValue ?? $defaultValue);
                            @endphp

                            @php
                                $isGeneratedCode = ($question->options['code_tag'] ?? '') === 'generated_code';

                                if ($isGeneratedCode) {
                                    // ¡ALTO! Es una pregunta especial. Forzamos el componente de sistema.
                                    // Asegúrate de que el archivo sea resources/views/components/inputs/system-code.blade.php
                                    $componentName = 'inputs.system-code';
                                } else {
                                    // 2. LÓGICA ESTÁNDAR
                                    // Si no es especial, usamos su tipo de base de datos (text, select, date...)
                                    $componentName = 'inputs.' . $question->type;
                                }
                                if (!view()->exists("components.{$componentName}")) {
                                    $componentName = 'inputs.text';
                                }

                            @endphp

                            {{-- 2. DECISIÓN DE DIBUJO: Oculto vs Visible --}}
                            @if ($esPreguntaProyecto)
                                {{-- Si es el proyecto, creamos un input oculto puro. No se verá nada en pantalla. --}}
                                <input type="hidden" name="{{ $fieldName }}" value="{{ $finalValue }}">
                            @elseif ($question->type != 'sub_form')
                                {{-- Si es cualquier otra pregunta normal, dibujamos tu componente dinámico --}}
                                <x-dynamic-component :component="$componentName" :question="$question" :value="$finalValue" />
                            @endif

                            @switch($question->type)
                                @case('sub_form')
                                    @php
                                        // 1. Identificamos qué sección incrustar
                                        $targetSectionId = $question->options['target_section_id'];

                                        // 2. Buscamos las preguntas de esa sección (Mejor si las pasas desde el controlador para optimizar)
                                        $childSection = \App\Models\Sections::with('questions')->find($targetSectionId);

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
                                        <div
                                            class="md:col-span-2 space-y-5 mt-4 border border-stone-400 rounded p-2  grid grid-cols-1 md:grid-cols-2 gap-4 items-center content-center">
                                            <h4 class="md:col-span-2 text-blue-800 font-bold mb-3 border-b-2 border-blue-500">
                                                {{ $childSection->title }} </h4>

                                            {{-- Iteramos las preguntas de la sección HIJA --}}
                                            @foreach ($childSection->questions as $childQ)
                                                @php
                                                    // Nombre correcto del campo
                                                    $childInputName = "sub_answers[{$question->id}][{$childQ->id}]";

                                                    // Valor (old > guardado > default)
                                                    $childValue = old(
                                                        "sub_answers.{$question->id}.{$childQ->id}",
                                                        $childAnswers[$childQ->id] ??
                                                            ($childQ->options['default_value'] ?? ''),
                                                    );

                                                    // Componente correcto según el tipo del HIJO
                                                    $childComponent = 'inputs.' . $childQ->type;

                                                    // Fallback de seguridad
                                                    if (!view()->exists("components.{$childComponent}")) {
                                                        $childComponent = 'inputs.text';
                                                    }
                                                @endphp

                                                <div class="mb-3">
                                                    <x-dynamic-component :component="$childComponent" :question="$childQ" :value="$childValue"
                                                        :name="$childInputName" />
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @break
                            @endswitch
                        @endforeach
                    </div>
                    <div class="flex justify-center mt-4 grid-cols-1 md:grid-cols-2">
                        <button type="submit"
                            class="bg-blue-600 text-xs hover:bg-blue-700 text-white font-bold py-1 px-4 rounded shadow-lg transition duration-150">
                            Guardar
                        </button>
                    </div>
            </form>
        </div>
</x-layouts::app>
