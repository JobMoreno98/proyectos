@php
    $profileUpdated = auth()->user()->hasUpdatedProfileThisYear();
@endphp


@foreach ($enlaces as $link)
 
    @can('view', $link)
        @if ($profileUpdated || $link->isDatosGenerales())
            <flux:sidebar.item icon="home" :href="route('seccion.show',$link->id)"
                :current="request()->route('categoria') === $link->id" wire:navigate>
                {{ $link->titulo }}
            </flux:sidebar.item>
        @endif
    @endcan
@endforeach