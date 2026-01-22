@props(['question', 'value'])

@php
    $name = "answers[{$question->id}]";
    $errorKey = "answers.{$question->id}";
    $rawFormats = $question->options['allowed_formats'] ?? '';
    $acceptAttribute = '';
    
    if ($rawFormats) {
        $formats = explode(',', $rawFormats);
        $extensions = array_map(function($ext) {
            return '.' . trim($ext);
        }, $formats);
        $acceptAttribute = implode(',', $extensions);
    }
@endphp

<x-inputs.wrapper :label="$question->label" :name="$errorKey" :required="$question->is_required">
    @if($value)
        <div class="flex items-center justify-between p-3 mb-2 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex items-center space-x-2 overflow-hidden">
                {{-- Icono Genérico --}}
                <svg class="w-6 h-6 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                
                <div class="flex flex-row">
                    
                    {{-- Si quieres mostrar el nombre real, necesitas lógica extra, si no, muestra 'Ver archivo' --}}
                    <a href="{{ Storage::url($value) }}" target="_blank" class="text-sm text-blue-600 underline truncate hover:text-blue-800">
                        Ver archivo
                    </a>
                </div>
            </div>
            @if(Str::endsWith(strtolower($value), ['jpg', 'jpeg', 'png', 'webp']))
                <div class="h-10 w-10 flex-shrink-0">
                    <img src="{{ Storage::url($value) }}" class="h-10 w-10 object-cover rounded shadow-sm">
                </div>
            @endif
        </div>        
        <p class="text-xs text-gray-500 mb-1">Si deseas reemplazar el archivo, selecciona uno nuevo abajo:</p>
    @endif
    <input 
        type="file" 
        name="{{ $name }}" 
        id="{{ $errorKey }}"
        accept="{{ $acceptAttribute }}"
        class="block w-full text-sm text-gray-500
            file:mr-4 file:py-2 file:px-4
            file:rounded-full file:border-0
            file:text-sm file:font-semibold
            file:bg-indigo-50 file:text-indigo-700
            hover:file:bg-indigo-100
            cursor-pointer border rounded-md border-gray-300"
    >
    
    @if($rawFormats)
        <p class="mt-1 text-xs text-gray-400">Formatos permitidos: {{ $rawFormats }}</p>
    @endif

</x-inputs.wrapper>