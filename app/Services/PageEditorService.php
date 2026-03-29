<?php

namespace App\Services;

use App\Events\EditorInitializedEvent;
use App\Models\ThemePage;

class PageEditorService
{
    protected $blocks = [];

    /**
     * Register a custom block.
     *
     * @param string $identifier
     * @param string $blockClass
     */
    public function registerBlock(string $identifier, string $blockClass): void
    {
        $this->blocks[$identifier] = $blockClass;
    }

    /**
     * Get all registered blocks.
     *
     * @return array
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * Render a page from its block content.
     *
     * @param ThemePage $page
     * @return string
     */
    public function renderPage(ThemePage $page): string
    {
        if (!$page->content || !is_array($page->content)) {
            return '';
        }

        $html = '';

        foreach ($page->content as $block) {
            $html .= $this->renderBlock($block);
        }

        return $html;
    }

    /**
     * Render a single block.
     *
     * @param array $block
     * @return string
     */
    protected function renderBlock(array $block): string
    {
        $type = $block['type'] ?? null;
        $config = $block['config'] ?? [];

        if (!$type || !isset($this->blocks[$type])) {
            // Unknown block type - render as HTML comment for debugging
            return "<!-- Unknown block type: {$type} -->";
        }

        $blockClass = $this->blocks[$type];

        if (!class_exists($blockClass)) {
            return "<!-- Block class not found: {$blockClass} -->";
        }

        try {
            $instance = new $blockClass();

            if (!method_exists($instance, 'render')) {
                return "<!-- Block class missing render method: {$blockClass} -->";
            }

            return $instance->render($config);
        } catch (\Exception $e) {
            return "<!-- Error rendering block: {$e->getMessage()} -->";
        }
    }

    /**
     * Initialize the editor and fire event for module registration.
     */
    public function initializeEditor(): void
    {
        event(new EditorInitializedEvent($this));
    }

    /**
     * Get block configuration for the editor UI.
     *
     * @return array
     */
    public function getBlocksConfig(): array
    {
        $configs = [];

        foreach ($this->blocks as $identifier => $blockClass) {
            if (class_exists($blockClass)) {
                $instance = new $blockClass();

                if (method_exists($instance, 'boot')) {
                    $configs[$identifier] = $instance->boot();
                }
            }
        }

        return $configs;
    }
}
