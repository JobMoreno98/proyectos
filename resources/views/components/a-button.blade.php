{{-- resources/views/components/a-button.blade.php --}}
@props(['href' => null, 'method' => null, 'color' => 'indigo', 'icon'])

@if ($href)
    <a href="{{ $href }}" class="text-{{ $color }}-600 hover:text-{{ $color }}-900 text-sm font-medium">
        <flux:icon.{{ $icon }} variant="solid" />
    </a>
@elseif ($method)
    <form action="{{ $href }}" method="POST" onsubmit="return confirm('¿Eliminar este registro?');">
        @csrf
        @method($method)
        <button type="submit" class="text-{{ $color }}-600 hover:text-{{ $color }}-900 text-sm font-medium">
            <flux:icon.{{ $icon }} variant="solid" />
        </button>
    </form>
@endif