<?php

namespace App\View\Components\Partials;

use Illuminate\View\Component;

class ButtonComponent extends Component
{
    public $label;
    public $color;
    public $type;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct( $label, $color, $type = 'submit' )
    {
        $this->color    =   $color;
        $this->label    =   $label;
        $this->type     =   $type;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.partials.button');
    }
}
