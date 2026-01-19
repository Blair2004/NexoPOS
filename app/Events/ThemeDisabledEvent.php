<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class ThemeDisabledEvent
{
    use SerializesModels;

    public $theme;

    /**
     * Create a new event instance.
     */
    public function __construct($theme)
    {
        $this->theme = $theme;
    }
}
