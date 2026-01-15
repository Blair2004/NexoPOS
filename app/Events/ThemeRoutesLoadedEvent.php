<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class ThemeRoutesLoadedEvent
{
    use SerializesModels;

    public $theme;
    public $routes;

    /**
     * Create a new event instance.
     */
    public function __construct($theme, $routes = [])
    {
        $this->theme = $theme;
        $this->routes = $routes;
    }
}
