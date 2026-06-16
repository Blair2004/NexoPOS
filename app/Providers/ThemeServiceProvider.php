<?php

namespace App\Providers;

use App\Services\Helper;
use App\Services\ThemeService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    protected ThemeService $themes;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(ThemeService $themes)
    {
        /**
         * We should only boot the theme if the system
         * is already installed.
         */
        if (Helper::installed()) {
            $enabledTheme = $themes->getEnabled();

            if ($enabledTheme) {
                /**
                 * Register theme views namespace
                 */
                if (isset($enabledTheme['path'])) {
                    $viewsPath = $enabledTheme['path'] . 'Views';
                    if (is_dir($viewsPath)) {
                        View::addNamespace('theme', $viewsPath);
                    }
                }

                /**
                 * Create symlink if needed
                 */
                try {
                    $themes->createSymLink($enabledTheme['namespace']);
                } catch (\Exception $e) {
                    // Log but don't fail if symlink creation fails
                    \Log::warning('Failed to create theme symlink: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ThemeService::class, function ($app) {
            $this->themes = new ThemeService;
            $this->themes->load();

            return $this->themes;
        });
    }
}
