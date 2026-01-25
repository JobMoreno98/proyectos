<x-layouts::app :title="__('Dashboard')" >
    <div class="flex h-full max-w-2xl justify-center content-center flex-1 flex-col justify-items-center gap-4 rounded-xl">
        <flux:heading size="lg">
            Bienvenido, {{ auth()->user()->name }}
        </flux:heading>

        {{-- 🔔 NOTIFICACIÓN --}}
        @if ($mustFillDatosGenerales)
            <flux:callout type="warning" class=" w-full">
                <flux:callout.heading>
                    Atención requerida
                </flux:callout.heading>

                <flux:callout.text>
                    Debes completar tus <strong>Datos generales</strong> para continuar.
                </flux:callout.text>


                <flux:button class="w-40" href="{{ route('seccion.show', $datosGeneralesCategoryId ?? 1) }}"
                    variant="primary">
                    Completar ahora
                </flux:button>

            </flux:callout>
        @endif
    </div>
</x-layouts::app>
