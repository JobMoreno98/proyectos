@php
    $titlePage = 'Asignar rol - ' . $user->name;
@endphp


<x-layouts::app :title="$titlePage">

    <div class="max-w-2xl mx-auto bg-white shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Asignar rol - {{ $user->name }}</h2>

        @if (session('success'))
            <x-alert type="success">
                {{ session('success') }}
            </x-alert>
        @endif

        <div>
            <label for="user_id" class="block text-sm font-medium text-gray-700">Usuario</label>
            <p>
                Nombre: {{ $user->name }} <br>
                Corre: {{ $user->email }}
            </p>
            @error('user_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <!-- Ícono de advertencia -->
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M12 5a7 7 0 100 14a7 7 0 000-14z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Se encontraron errores:</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <form action="{{ route('user.assignRole', $user->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Lista de roles como checkboxes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Roles</label>
                <div class="space-y-2">
                    @foreach ($roles as $role)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                {{ $user->hasRole($role->name) ? 'checked' : '' }}>
                            <span class="text-gray-700">{{ ucfirst($role->name) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('roles')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit"
                    class="w-full bg-indigo-600 text-white font-semibold py-2 px-4 rounded-md shadow hover:bg-indigo-700 transition">
                    Actualizar Roles
                </button>
            </div>
        </form>

    </div>

</x-layouts::app>
