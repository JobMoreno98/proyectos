@php
    $titlePage = 'Editar - ' . $seccion->title;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            {{ $titlePage }}
        </h2>
    </x-slot>


    <div class="container m-auto">
        <div class="max-w-3xl mx-auto py-10 px-4">
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    {{ session('success') }}
                </div>
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

            <form action="{{ route('proyectos.update', $entry->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT') {{-- Importante para update --}}
                <div class="bg-white shadow rounded-lg p-6 border-t-2 border-blue-500">
                    {{-- Enviamos el ID de sección para la validación (StoreSurveyRequest) --}}
                    <input type="hidden" name="section_ids[]" value="{{ $seccion->id }}">

                    {{-- ... Título de sección ... --}}

                    @foreach ($seccion->questions as $question)
                        @php
                            $fieldName = "answers[{$question->id}]";
                            $errorKey = "answers.{$question->id}";
                            $dbValue = $existingAnswers[$question->id] ?? null;
                        @endphp

                        <div class="mb-4">
                            <label>{{ $question->label }}</label>

                            @switch($question->type)
                                @case('text')
                                    <input type="text" name="{{ $fieldName }}" {{-- El segundo parámetro de old() es el valor por defecto (BD) --}}
                                        value="{{ old($errorKey, $dbValue) }}"
                                        class="border-gray-300 text-stone-900 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 w-full">
                                @break

                                @case('textarea')
                                    <textarea name="{{ $fieldName }}"
                                        class="text-stone-900 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 w-full">{{ old($errorKey, $dbValue) }}</textarea>
                                @break

                                @case('select')
                                    <select name="{{ $fieldName }}"
                                        class="text-stone-900 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 w-full">
                                        <option value="">Seleccione...</option>
                                        @forelse ($question->options['choices'] as $key => $label)
                                            <option value="{{ $key }}" {{-- Comparamos contra old() O contra el valor de BD --}}
                                                {{ old($errorKey, $dbValue) == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @empty
                                            <option> </option>
                                        @endforelse

                                    </select>
                                @break

                                @case('date')
                                    @php $opts = $question->options ?? []; @endphp
                                    <input type="date" name="{{ $fieldName }}" value="{{ old($errorKey, $dbValue) }}"
                                        min="{{ $opts['min_date'] ?? '' }}" max="{{ $opts['max_date'] ?? '' }}"
                                        class="form-input w-full">
                                @break

                                @case('file')
                                    {{-- Mostrar enlace al archivo actual si existe --}}
                                    @if ($dbValue)
                                        <div class="text-sm text-gray-600 mb-2">
                                            Archivo actual: <a href="{{ asset('storage/' . $dbValue) }}" target="_blank"
                                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">Ver
                                                archivo</a>
                                        </div>
                                    @endif

                                    <input type="file" name="{{ $fieldName }}"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <p class="text-xs text-gray-500">Deja esto vacío para mantener el archivo actual.</p>
                                @break
                            @endswitch

                            @error($errorKey)
                                <span class="text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    @endforeach
                    <div class="flex justify-center mt-4">
                        <button type="submit"
                            class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded shadow-lg transition duration-150">
                            Actualizar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
