<?php

namespace App\Http\Controllers;

use App\Events\PageRenderingEvent;
use App\Models\ThemePage;
use App\Models\ThemeSlug;
use App\Services\PageEditorService;
use App\Services\ThemeService;
use Illuminate\Http\Request;

class ThemeFrontendController extends Controller
{
    public function __construct(
        protected ThemeService $themeService,
        protected PageEditorService $pageEditorService
    ) {
    }

    /**
     * Display blog home page
     */
    public function blog(Request $request)
    {
        $theme = $this->themeService->getEnabled();

        if (!$theme) {
            abort(404);
        }

        // Check if theme has blog feature
        if (!isset($theme['features']['blog'])) {
            abort(404);
        }

        // Load blog posts (you would implement this based on your blog system)
        $posts = []; // Placeholder

        return view('theme::blog', compact('posts', 'theme'));
    }

    /**
     * Display single blog post
     */
    public function blogSingle(Request $request, $slug)
    {
        $theme = $this->themeService->getEnabled();

        if (!$theme) {
            abort(404);
        }

        // Check if theme has blog feature
        if (!isset($theme['features']['blog'])) {
            abort(404);
        }

        // Load blog post by slug (you would implement this based on your blog system)
        $post = null; // Placeholder

        if (!$post) {
            abort(404);
        }

        return view('theme::blog-single', compact('post', 'theme'));
    }

    /**
     * Display store home page
     */
    public function store(Request $request)
    {
        $theme = $this->themeService->getEnabled();

        if (!$theme) {
            abort(404);
        }

        // Check if theme has store feature
        if (!isset($theme['features']['store'])) {
            abort(404);
        }

        // Load products (you would integrate with NexoPOS product system)
        $products = []; // Placeholder

        return view('theme::store', compact('products', 'theme'));
    }

    /**
     * Display single product page
     */
    public function product(Request $request, $id)
    {
        $theme = $this->themeService->getEnabled();

        if (!$theme) {
            abort(404);
        }

        // Check if theme has store feature
        if (!isset($theme['features']['store'])) {
            abort(404);
        }

        // Load product by ID (you would integrate with NexoPOS product system)
        $product = null; // Placeholder

        if (!$product) {
            abort(404);
        }

        return view('theme::product', compact('product', 'theme'));
    }

    /**
     * Display shopping cart
     */
    public function cart(Request $request)
    {
        $theme = $this->themeService->getEnabled();

        if (!$theme) {
            abort(404);
        }

        // Check if theme has store feature
        if (!isset($theme['features']['store'])) {
            abort(404);
        }

        return view('theme::cart', compact('theme'));
    }

    /**
     * Display checkout page
     */
    public function checkout(Request $request)
    {
        $theme = $this->themeService->getEnabled();

        if (!$theme) {
            abort(404);
        }

        // Check if theme has store feature
        if (!isset($theme['features']['store'])) {
            abort(404);
        }

        return view('theme::checkout', compact('theme'));
    }

    /**
     * Display a page
     */
    public function page(Request $request, $slug)
    {
        $theme = $this->themeService->getEnabled();

        if (!$theme) {
            abort(404);
        }

        // Check if theme has pages feature
        if (!isset($theme['features']['pages'])) {
            abort(404);
        }

        // Find page by slug
        $page = ThemePage::where('slug', $slug)
            ->where('status', 'published')
            ->where('theme_namespace', $theme['namespace'])
            ->first();

        // If not found, try to find by full path (for nested pages)
        if (!$page) {
            $slugParts = explode('/', $slug);
            $lastSlug = array_pop($slugParts);

            $page = ThemePage::where('slug', $lastSlug)
                ->where('status', 'published')
                ->where('theme_namespace', $theme['namespace'])
                ->first();

            // Verify the full path matches
            if ($page && $page->full_path !== $slug) {
                $page = null;
            }
        }

        if (!$page) {
            abort(404);
        }

        // Render page content
        $content = $this->pageEditorService->renderPage($page);

        // Fire event for modification
        event(new PageRenderingEvent($page, $content));

        return view('theme::page', compact('page', 'content', 'theme'));
    }

    /**
     * Display search results
     */
    public function search(Request $request)
    {
        $theme = $this->themeService->getEnabled();

        if (!$theme) {
            abort(404);
        }

        $query = $request->input('q', '');
        $results = []; // Placeholder - implement search logic

        return view('theme::search', compact('query', 'results', 'theme'));
    }
}
