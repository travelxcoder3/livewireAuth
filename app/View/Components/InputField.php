<?php

namespace App\View\Components;

use Illuminate\View\Component;

class InputField extends Component
{
    public $wireModel;
    public $label;
    public $placeholder;
    public $type = 'text';
    public $containerClass;
    public $fieldClass;
    public $labelClass;
    public $options = [];
    public $isSelect = false;

    public function render()
    {
        return view('components.input-field');
    }
}