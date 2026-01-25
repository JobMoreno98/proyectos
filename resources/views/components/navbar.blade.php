@php
    $profileUpdated = auth()->user()->hasUpdatedProfileThisYear();
@endphp


@foreach ($enlaces as $link)
    @if ($profileUpdated || $link->isDatosGenerales())
        <flux:navbar.item icon="home" :href="route('seccion.show',$link->id)"
            :current="request()->route('categoria') === $link->id" wire:navigate>

            {{ $link->titulo }}
        </flux:navbar.item>
    @endif
@endforeach
