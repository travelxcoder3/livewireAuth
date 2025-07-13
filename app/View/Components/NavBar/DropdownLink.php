<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DropdownLink extends Component
{
    public $href, $icon, $label, $show;
    public function __construct($href = null, $icon = null, $label = null, $show = true)
    {
        $this->href = $href;
        $this->icon = $icon;
        $this->label = $label;
        $this->show = filter_var($show, FILTER_VALIDATE_BOOLEAN);
    }
    public function render()
    {
        return view('components.navbar.buttons.dropdown-link');
    }
} 