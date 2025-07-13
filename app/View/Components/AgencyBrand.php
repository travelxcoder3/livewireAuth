<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AgencyBrand extends Component
{
    public $name, $logo, $class;
    public function __construct($name = null, $logo = null, $class = null)
    {
        $this->name = $name ?? (auth()->user()->agency->name ?? 'Travel X');
        $this->logo = $logo ?? (auth()->user()->agency->logo ?? null);
        $this->class = $class ?? '';
    }
    public function render()
    {
        return view('components.navbar.brand.agency-brand');
    }
} 