<?php

namespace App\View\Components;

use Illuminate\View\Component;

class IconButton extends Component
{
    /**
     * The icon class (مثلاً: fa fa-home).
     * @var string
     */
    public $icon;

    /**
     * Additional classes for the button.
     * @var string|null
     */
    public $class;

    /**
     * Tooltip text (optional).
     * @var string|null
     */
    public $tooltip;

    public $href;
    public $label;
    public $active;
    public $dropdown;

    /**
     * Create a new component instance.
     *
     * @param string|null $icon
     * @param string|null $class
     * @param string|null $tooltip
     * @param string|null $href
     * @param string|null $label
     * @param bool|null $active
     * @param bool|null $dropdown
     */
    public function __construct($icon = null, $class = null, $tooltip = null, $href = null, $label = null, $active = false, $dropdown = false)
    {
        $this->icon = $icon;
        $this->class = $class;
        $this->tooltip = $tooltip;
        $this->href = $href;
        $this->label = $label;
        $this->active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
        $this->dropdown = filter_var($dropdown, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.navbar.buttons.icon-button');
    }
} 