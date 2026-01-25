<?php

namespace App\View\Components;

use App\Models\Categorias;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Navbar extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $categorias = Categorias::whereJsonContains('sistema', 'investigacion')->orderBy('titulo')->get();
        
        $categorias = $categorias->map(function ($categoria) {
            if ($categoria->titulo === 'Generación y Aplicación del Conocimiento') {
                $categoria->titulo = 'Proyectos de investigación';
            }
            return $categoria;
        });


        return view('components.navbar', ['enlaces' => $categorias]);
    }
}
