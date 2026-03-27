<x-layouts::app :title="__('Dashboard')">
    @if (session('success'))
        <x-alert type="success">
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="grid place-items-center min-h-[80vh] w-full px-4">

        <div class="flex w-full max-w-2xl flex-col items-center gap-6 rounded-xl text-center">

            <flux:heading size="lg">
                Bienvenido, {{ auth()->user()->name }}
            </flux:heading>

            @if ($mustFillDatosGenerales)
                <flux:callout type="warning" class="w-full text-left">
                    <flux:callout.heading>
                        Atención requerida
                    </flux:callout.heading>

                    <flux:callout.text>
                        Debes completar tus <strong>Datos generales</strong> para continuar.
                    </flux:callout.text>

                    <div class="mt-4">
                        <flux:button class="w-40" href="{{ route('seccion.show', $datosGeneralesCategoryId ?? 1) }}"
                            variant="primary">
                            Completar ahora
                        </flux:button>
                    </div>
                </flux:callout>
            @endif
        </div>
    </div>
</x-layouts::app>
