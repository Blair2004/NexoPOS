<?php

namespace App\Providers;

use App\View\Components\Partials\ButtonComponent;
use App\View\Components\Partials\LinkComponent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeComponentsProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::component( 'ns-button', ButtonComponent::class);
        Blade::component( 'ns-link', LinkComponent::class);
    }
}
