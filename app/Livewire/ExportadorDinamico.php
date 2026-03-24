<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sections;
use App\Models\Categorias;
use App\Models\Question;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RespuestasExport;

class ExportadorDinamico extends Component
{
    public $secciones;
    public $subSecciones = []; // Aquí guardaremos los sub-formularios
    public array $preguntasSeleccionadas = [];

    public function mount()
    {
        // 1. Buscamos las secciones principales
        $categoriasIds = Categorias::whereJsonContains('sistema', 'investigacion')->pluck('id');

        $this->secciones = Sections::whereIn('categoria_id', $categoriasIds)
            ->with(['questions' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->orderBy('sort_order')->where('investigacion', 1)
            ->get();

        // 2. RASTREAMOS LOS SUB-FORMULARIOS
        $subSectionIds = [];
        foreach ($this->secciones as $seccion) {
            foreach ($seccion->questions as $q) {
                if ($q->type === 'sub_form' && !empty($q->options['target_section_id'])) {
                    $subSectionIds[] = $q->options['target_section_id'];
                }
            }
        }

        // 3. Cargamos los sub-formularios encontrados a la memoria
        if (!empty($subSectionIds)) {
            $this->subSecciones = Sections::whereIn('id', array_unique($subSectionIds))
                ->with(['questions' => function ($q) {
                    $q->orderBy('sort_order');
                }])
                ->get()
                ->keyBy('id'); // Las indexamos por ID para buscarlas rapidísimo en la vista
        }
    }
    public function seleccionarTodo()
    {
        $ids = [];

        // 1. Recolectamos los IDs de las preguntas normales
        foreach ($this->secciones as $seccion) {
            foreach ($seccion->questions as $pregunta) {
                if ($pregunta->type !== 'sub_form') {
                    $ids[] = $pregunta->id;
                }
            }
        }

        // 2. Recolectamos los IDs de los sub-formularios
        foreach ($this->subSecciones as $subSeccion) {
            foreach ($subSeccion->questions as $pregunta) {
                $ids[] = $pregunta->id;
            }
        }

        // 3. Llenamos el arreglo de Livewire de un solo golpe
        $this->preguntasSeleccionadas = $ids;
    }

    public function deseleccionarTodo()
    {
        // Vaciamos el arreglo
        $this->preguntasSeleccionadas = [];
    }
    public function procesarExportacion()
    {
        if (empty($this->preguntasSeleccionadas)) {
            session()->flash('error', 'Por favor, selecciona al menos una pregunta para exportar.');
            return;
        }
        return Excel::download(
            new RespuestasExport($this->preguntasSeleccionadas),
            'reporte_personalizado_' . date('Ymd_His') . '.xlsx'
        );
    }

    public function render()
    {
        return view('livewire.exportador-dinamico')->layout('layouts.app');
    }
}
