<?php

namespace App\View\Components\Partials;

use Illuminate\View\Component;

class LinkComponent extends Component
{
    public $label;
    public $to;
    public $type;
    public $href;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct( $label, $to = '', $href = '', $type = 'info' )
    {
        $this->label        =   $label;
        $this->to           =   $to;
        $this->type         =   $type;
        $this->href         =   $href;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.partials.link-component');
    }
}
