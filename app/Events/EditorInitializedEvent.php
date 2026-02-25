<?php

namespace App\Events;

use App\Services\PageEditorService;
use Illuminate\Queue\SerializesModels;

class EditorInitializedEvent
{
    use SerializesModels;

    public $pageEditorService;

    /**
     * Create a new event instance.
     */
    public function __construct(PageEditorService $pageEditorService)
    {
        $this->pageEditorService = $pageEditorService;
    }
}
