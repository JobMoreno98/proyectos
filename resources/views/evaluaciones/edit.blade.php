@php
    $titlePage = 'Editar - ' . $seccion->title;
@endphp
<x-layouts::app :title="$titlePage">
    <div class="container m-auto">
        <div class="max-w-7xl mx-auto py-10 px-4">
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

            <form action="{{ route('evaluacion.update', $entry->id) }}" method="POST" enctype="multipart/form-data"
                class="bg-white shadow-lg rounded-lg p-6 border-t-2 border-blue-500">
                @csrf
                @method('PUT')
                <h3 class="font-semibold text-gray-800 text-center">{{ $seccion->title }}</h3>
                @if ($seccion->description)
                    <p class="text-gray-500 text-sm mb-4">{{ $seccion->description }}</p>
                @endif


                <div class="space-y-5 mt-4  grid grid-cols-1 md:grid-cols-2 gap-4 items-center content-center">

                    <input type="hidden" name="section_ids[]" value="{{ $seccion->id }}">

                    @foreach ($seccion->questions as $question)

                        @php
                            $fieldName = "answers[{$question->id}]";
                            $errorKey = "answers.{$question->id}";
                            $savedValue = $existingAnswers[$question->id] ?? null;

                            $defaultValue = $question->options['default_value'] ?? '';

                            $esPreguntaProyecto = strtolower(trim($question->label)) === 'proyecto';

                            if ($esPreguntaProyecto) {
                                $defaultValue = $proyectoID ?? null;
                            }

                            $finalValue = old($errorKey, $savedValue ?? $defaultValue);

                            $componentName = 'inputs.' . $question->type;
                            if (!view()->exists("components.{$componentName}")) {
                                $componentName = 'inputs.text';
                            }
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

                            // 3. SEGURIDAD (FALLBACK)
                            // Si el componente (system-code o el tipo normal) no existe físicamente, usamos 'text'
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

                        {{-- Sub_form --}}
                        @if ($question->type === 'sub_form')
                            @php
                                $targetSectionId = $question->options['target_section_id'];
                                $childSection = \App\Models\Sections::with('questions')->find($targetSectionId);

                                // Entry del sub_form (nuevo o existente)
                                $childEntryId = $existingAnswers[$question->id] ?? null;

                                $childAnswers = [];
                                if ($childEntryId) {
                                    $childEntry = \App\Models\Entry::with('answers')->find($childEntryId);
                                    $childAnswers = $childEntry->answers->pluck('value', 'question_id')->toArray();
                                }
                            @endphp

                            @if ($childSection)
                                <div
                                    class="md:col-span-2 space-y-5 mt-4 border border-stone-400 rounded p-2  grid grid-cols-1 md:grid-cols-2 gap-4 items-center content-center">
                                    <h4 class="md:col-span-2 text-blue-800 font-bold mb-3 border-b-2 border-blue-500">
                                        {{ $childSection->title }}
                                    </h4>

                                    @foreach ($childSection->questions as $childQ)
                                        @php
                                            $childInputName = "sub_answers[{$question->id}][{$childQ->id}]";
                                            $childErrorKey = "sub_answers.{$question->id}.{$childQ->id}";

                                            $childValue = old(
                                                $childErrorKey,
                                                $childAnswers[$childQ->id] ?? ($childQ->options['default_value'] ?? ''),
                                            );

                                            $childComponent = 'inputs.' . $childQ->type;
                                            if (!view()->exists("components.{$childComponent}")) {
                                                $childComponent = 'inputs.text';
                                            }
                                        @endphp

                                        <div class="mb-3">
                                            <x-dynamic-component :component="$childComponent" :question="$childQ" :value="$childValue"
                                                :name="(string) $childInputName" />
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    @endforeach



                    <div class="flex justify-center mt-4 md:col-span-2">
                        <button type="submit"
                            class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded shadow-lg transition duration-150">
                            Actualizar
                        </button>

                        <a href="{{ route('proyectos.send', $entry->id) }}"
                            class="mx-2 flex uppercase text-center items-center gap-1 text-green-600 hover:text-yellow-900 text-sm font-medium">
                            <flux:icon.check-circle variant="mini" />
                            Enviar definitivo
                        </a>
                    </div>
                </div>

            </form>
        </div>
</x-layouts::app>
