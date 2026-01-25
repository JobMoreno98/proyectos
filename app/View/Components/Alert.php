<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Alert extends Component
{
    public string $type;
    public int $timeout;

    public function __construct(string $type = 'success', int $timeout = 2000)
    {
        $this->type = $type;
        $this->timeout = $timeout; // milisegundos
    }

    public function render()
    {
        return view('components.alert');
    }
}
