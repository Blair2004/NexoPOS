<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class PageRenderingEvent
{
    use SerializesModels;

    public $page;
    public $content;

    /**
     * Create a new event instance.
     */
    public function __construct($page, &$content)
    {
        $this->page = $page;
        $this->content = &$content;
    }
}
