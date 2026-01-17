<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class MenuRenderingEvent
{
    use SerializesModels;

    public $menu;
    public $menuItems;

    /**
     * Create a new event instance.
     */
    public function __construct($menu, &$menuItems)
    {
        $this->menu = $menu;
        $this->menuItems = &$menuItems;
    }
}
