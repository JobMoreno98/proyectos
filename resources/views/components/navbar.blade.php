@php
    $profileUpdated = auth()->user()->hasUpdatedProfileThisYear();
   
@endphp


@foreach ($enlaces as $link)
    @can('view', $link)
        @if ($profileUpdated || $link->isDatosGenerales())
            <flux:navbar.item icon="home" :href="route('seccion.show',$link->id)"
                :current="request()->route('categoria') === $link->id" >
                {{ $link->titulo }}
            </flux:navbar.item>
        @endif
    @endcan
@endforeach
