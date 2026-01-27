{{-- Agrega 'label' y 'folio' a los props --}}
@props(['type', 'value', 'options' => [], 'label' => '', 'folio' => ''])

@php
    if (blank($value)) {
        echo '<span class="text-gray-400 italic text-sm">Sin respuesta</span>';
        return;
    }
@endphp
@switch($type)

    {{-- CASO ESPECIAL: SCORED TEXT (Tu nuevo campo) --}}
    @case('scored_text')
        <div class="flex items-start gap-4">
            {{-- Círculo con la nota --}}
            <div class="flex-shrink-0">
                @php $score = $value['score'] ?? 0; @endphp
                <div
                    class="flex items-center justify-center h-12 w-12 rounded-full border-2 font-bold text-xl
                    {{ $score >= 8
                        ? 'border-green-500 bg-green-50 text-green-700'
                        : ($score >= 5
                            ? 'border-yellow-500 bg-yellow-50 text-yellow-700'
                            : 'border-red-500 bg-red-50 text-red-700') }}">
                    {{ $score }}
                </div>
            </div>
            {{-- Texto Justificación --}}
            <div class="flex-grow bg-gray-50 rounded p-3 text-gray-700 border border-gray-200">
                <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Justificación:</span>
                "{!! $value['text'] ?? '' !!}"
            </div>
        </div>
    @break

    @case('textarea')
        <div class="flex items-start gap-4">
            <div class="flex-grow bg-gray-50 rounded p-3 text-gray-700 border border-gray-200">
                {!! $value !!}
            </div>
        </div>
    @break

    {{-- CASO SELECT: Buscar el Label bonito --}}
    @case('select')
        @php
            $display = $value;
            $choices = $options['choices'] ?? [];

            // Lógica para encontrar el label según si es array simple o asociativo
            if (is_array($choices)) {
                foreach ($choices as $k => $c) {
                    // Si es ['val' => 'Label']
                    if (!is_array($c) && $k == $value) {
                        $display = $c;
                        break;
                    }
                    // Si es [['value'=>'val', 'label'=>'Label']]
                    if (is_array($c) && ($c['value'] ?? '') == $value) {
                        $display = $c['label'];
                        break;
                    }
                }
            }
        @endphp
        <span class="inline-flex items-center px-3 py-1 rounded-sm text-sm font-medium bg-blue-100 text-blue-800">
            {{ $display }}
        </span>
    @break

    @case('file')
        <div class="mt-1">
            @php
                $url = Storage::url($value);
                $extension = pathinfo($value, PATHINFO_EXTENSION);
                if ($extension) {
                    $folio .= '.' . $extension;
                }
            @endphp

            <a href="{{ $url }}" target="_blank" download="{{ $folio }}"
                class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 hover:underline transition-colors font-medium break-all">
                {{-- Icono --}}
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13">
                    </path>
                </svg>

                {{-- Nombre Personalizado --}}
                {{ $folio }}
            </a>
        </div>
    @break

    {{-- CASO REFERENCE Y TEXTO NORMAL --}}

    @default
        <div class="text-gray-800">
            @if (is_array($value))
                {{-- Por si acaso llega un array no controlado --}}
                <pre class="text-xs">{{ json_encode($value) }}</pre>
            @else
                {{ $value }}
            @endif
        </div>

@endswitch
