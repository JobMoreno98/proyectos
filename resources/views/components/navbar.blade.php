@php
    $profileUpdated = auth()->user()->hasUpdatedProfileThisYear();

@endphp

@foreach ($enlaces as $link)
    @can('view', $link)
        @if ($profileUpdated || $link->isDatosGenerales())
            <flux:sidebar.item icon="home" :href="route('seccion.show', $link->id)"
                :current="request()->routeIs('seccion.show') && (request()->route('categoria')->id == $link->id )"
                wire:navigate>
                {{ $link->titulo }}
            </flux:sidebar.item>
        @endif
    @endcan
@endforeach
@if (Auth::user()->hasRole('admin'))
    <flux:sidebar.item icon="home" :href="route('export.data')" :current="request()->routeIs('export.data')"
        wire:navigate>
        Exportaciones
    </flux:sidebar.item>
@endif
