<?php

namespace App\Services;

use Illuminate\Support\Facades\View;

class WizardService
{
    public function __construct( private string $title, private string $description, private array $steps )
    {
        // ...
    }

    public function render()
    {
        return View::make( 'components.wizard.body' );
    }
}
