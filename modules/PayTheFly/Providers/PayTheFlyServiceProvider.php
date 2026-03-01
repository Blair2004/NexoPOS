<?php

namespace Modules\PayTheFly\Providers;

use App\Classes\Hook;
use Illuminate\Support\ServiceProvider;
use Modules\PayTheFly\Services\PayTheFlyService;

class PayTheFlyServiceProvider extends ServiceProvider
{
    /**
     * Register the PayTheFly service into the container.
     */
    public function register(): void
    {
        $this->app->singleton( PayTheFlyService::class, function () {
            return new PayTheFlyService;
        });
    }

    /**
     * Boot the module — register event listeners and hooks.
     */
    public function boot(): void
    {
        // Register our settings page into the NexoPOS settings menu
        Hook::addFilter( 'ns-dashboard-menus', function ( $menus ) {
            if ( isset( $menus[ 'modules' ] ) ) {
                $menus[ 'modules' ][ 'childrens' ][ 'paythefly' ] = [
                    'label' => __( 'PayTheFly Settings' ),
                    'permissions' => [ 'nexopos.manage-payments-types' ],
                    'href' => ns()->route( 'ns.dashboard.modules-settings', [ 'identifier' => 'paythefly' ] ),
                ];
            }

            return $menus;
        });
    }
}
