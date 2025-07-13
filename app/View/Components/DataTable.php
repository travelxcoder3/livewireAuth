<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DataTable extends Component
{
    public $rows;
    public $columns;

    public function __construct($rows, $columns)
    {
        $this->rows = $rows;
        $this->columns = $columns;
    }

    public function render()
    {
        return view('components.data-table');
    }
} 