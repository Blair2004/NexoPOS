<?php

namespace App\Providers;

use App\Models\Role;
use App\Services\Helper;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment( 'local' );

        Telescope::filter( function ( IncomingEntry $entry ) use ( $isLocal ) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        } );
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ( $this->app->environment( 'local' ) ) {
            return;
        }

        Telescope::hideRequestParameters( ['_token'] );

        Telescope::hideRequestHeaders( [
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ] );
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        if ( Helper::installed() ) {
            $adminRole = Role::namespace( Role::ADMIN );
            $users = collect( [] );

            if ( $adminRole instanceof Role ) {
                $users = $adminRole->users;
            }

            Gate::define( 'viewTelescope', function ( $user ) use ( $users ) {
                return in_array( $user->email, [
                    $users->map( fn( $__user ) => $__user->email ),
                ] );
            } );
        }
    }
}
