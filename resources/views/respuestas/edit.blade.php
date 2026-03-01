@php
    $titlePage = 'Editar - ' . $seccion->title;
    use App\Helpers\QuestionHelper;
@endphp
<x-layouts::app :title="$titlePage">
    <div class="container m-auto">
        <div class="max-w-7xl mx-auto py-10 px-4">
            @if (session('success'))
                <x-alert type="success">
                    {{ session('success') }}
                </x-alert>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('proyectos.update', $entry->id) }}" method="POST" enctype="multipart/form-data"
                class="bg-white shadow-lg rounded-lg p-6 border-t-2 border-blue-500  border border-stone-400 rounded">
                @csrf
                @method('PUT')
                <h3 class="font-semibold text-gray-800 text-center">{{ $seccion->title }}</h3>
                @if ($seccion->description)
                    <p class="text-gray-500 text-md mb-4 mt-1 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $seccion->description }}
                    </p>
                @endif
                <div class="space-y-5 mt-4  grid grid-cols-1 md:grid-cols-2 gap-4 items-center content-center">

                    <input type="hidden" name="section_ids[]" value="{{ $seccion->id }}">

                    @foreach ($seccion->questions as $question)
                        @php
                            $item = QuestionHelper::prepare($question, $existingAnswers);
                            $isSubForm = $item['type'] === 'sub_form';
                        @endphp

                        <div @if ($item['isDependent'] && $item['parentId']) x-data="dependencyComponent({{ $item['parentId'] }}, @js($item['expectedValue']))"
            x-init="init()"
            x-show="show"
x-collapse.duration.300ms
            x-cloak @endif
                            class="
            w-full mb-4
            {{ $isSubForm ? 'col-span-1 md:col-span-2' : '' }}
        ">

                            @if ($isSubForm)
                                <x-sub-form :item="$item" />
                            @else
                                <x-dynamic-component :component="$item['component']" :question="$item['model']" :value="$item['value']" />
                            @endif

                        </div>
                    @endforeach


                    <div class="flex justify-center mt-4 md:col-span-2">
                        <button type="submit"
                            class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded shadow-lg transition duration-150">
                            Actualizar
                        </button>
                    </div>
                </div>

            </form>
        </div>
</x-layouts::app>
