<?php

namespace App\Livewire;

use App\Models\Categorias;
use Livewire\Component;

class NavigationBarMobile extends Component
{
    public function render()
    {
        return view(
            'livewire.navigation-bar-mobile',
            ['enlaces' => Categorias::orderBy('titulo')->get()]
        );
    }
}
