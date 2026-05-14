<x-layouts::app :title="__('Dashboard')">
    @if (session('success'))
        <x-alert type="success">
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="grid place-items-center min-h-[80vh] w-full px-4">
        <x-flux::button size="sm" :href="route('evaluaciones.generar')"
            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow flex items-center gap-2 transition">
            Exportar evaluaciones

        </x-flux::button>
        <div class="flex w-full max-w-6xl flex-col items-center gap-6 rounded-xl text-center">

            @if (Auth::user()->hasRole('admin'))
                <livewire:exportador-dinamico></livewire:exportador-dinamico>
            @endif

        </div>

    </div>
</x-layouts::app>
