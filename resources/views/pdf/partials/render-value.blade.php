@php
    if (blank($value) && $type != 'file') {
        echo '<span class="text-muted">Sin respuesta</span>';
        return;
    }
@endphp

@switch($type)
    @case('scored_text')
        <strong>Puntuación:</strong> {{ $value['score'] ?? 0 }} / 10 <br>
        <strong>Justificación:</strong> {!! $value['text'] ?? '' !!}
    @break

    @case('textarea')
        {!! ($value) !!}
    @break

    @case('repeater_awards')
        @if(is_array($value))
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($value as $award)
                    <li>{{ $award['nombre'] ?? 'N/A' }} ({{ $award['tipo'] ?? '' }})</li>
                @endforeach
            </ul>
        @endif
    @break

    @case('select')
        @php
            $display = $value;
            $choices = $options['choices'] ?? [];
            if (is_array($choices)) {
                foreach ($choices as $k => $c) {
                    if (!is_array($c) && $k == $value) { $display = $c; break; }
                    if (is_array($c) && ($c['value'] ?? '') == $value) { $display = $c['label']; break; }
                }
            }
        @endphp
        {{ $display }}
    @break
    
    @default
        @if(is_array($value))
            {{ implode(', ', $value) }}
        @else
            {{ $value }}
        @endif
@endswitch