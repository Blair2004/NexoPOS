<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Http\Requests\SettingsRequest;
use App\Services\SettingsPage;
use Exception;
use Illuminate\Support\Facades\Gate;
use TorMorten\Eventy\Facades\Events as Hook;

class SettingsController extends DashboardController
{
    public function getSettings( $identifier )
    {
        Gate::allows( 'manages.options' );

        return $this->handleDefaultSettings( $identifier );
    }

    public function handleDefaultSettings( $identifier )
    {
        $settings = Hook::filter( 'ns.settings', false, $identifier );

        if ( $settings instanceof SettingsPage ) {
            return $settings->renderForm();
        }

        return abort( 404, __( 'Settings Page Not Found' ) );
    }

    /**
     * Get settings form using the identifier
     *
     * @param string identifier
     * @return array
     */
    public function getSettingsForm( $identifier )
    {
        $settings = Hook::filter( 'ns.settings', false, $identifier );

        if ( $settings instanceof SettingsPage ) {
            return $settings->getForm();
        }

        throw new Exception( __( 'Unable to initiallize the settings page. The identifier "' . $identifier . '", doesn\'t belong to a valid SettingsPage instance.' ) );
    }

    public function saveSettingsForm( SettingsRequest $request, $identifier )
    {
        ns()->restrict( [ 'manage.options' ] );

        $resource = Hook::filter( 'ns.settings', false, $identifier );

        if ( ! $resource instanceof SettingsPage ) {
            throw new Exception( sprintf(
                __( '%s is not an instance of "%s".' ),
                $identifier,
                SettingsPage::class
            ) );
        }

        return $resource->saveForm( $request );
    }
}
