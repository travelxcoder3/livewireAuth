<?php

namespace App\Http\Livewire;

use Livewire\Component;

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
    public $isSelect = false; // أضف هذا السطر

    public function render()
    {
        return view('livewire.input-field');
    }
}