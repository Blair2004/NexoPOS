<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Http\Requests\SettingsRequest;
use App\Services\SettingsPage;
use Exception;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Hook;

class SettingsController extends DashboardController
{
    private $taxService;

    public function __construct()
    {
        parent::__construct();
    }

    public function getSettings( $identifier )
    {
        switch( $identifier ) {
            case 'customers'; return $this->customersSettings(); break;
            case 'general'; return $this->generalSettings(); break;
            case 'invoices'; return $this->invoiceSettings(); break;
            case 'orders'; return $this->ordersSettings(); break;
            case 'pos'; return $this->posSettings(); break;
            case 'providers'; return $this->providersSettings(); break;
            case 'reports'; return $this->reportsSettings(); break;
            case 'reset'; return $this->resetSettings(); break;
            case 'services'; return $this->servicesSettings(); break;
            case 'stores'; return $this->storesSettings(); break;
        }
    }

    /**
     * Get settings form using the identifier
     * @param string identifier
     * @return array
     */
    public function getSettingsForm( $identifier )
    {
        $settings       =   Hook::filter( 'ns.settings', false, $identifier );

        if ( $settings instanceof SettingsPage ) {
            return $settings->getForm();
        }

        throw new Exception( __( 'Unable to initiallize the settings page. The identifier "'. $identifier . ', hasn\'t returned any SettingsPage instance."' ) );
    }

    public function customersSettings()
    {
        return $this->view( 'pages.dashboard.settings.general', [
            'title'     =>      __( 'Customers Settings' ),
            'description'   =>  __( 'Configure the customers settings of the application.' )
        ]);
    }

    public function generalSettings()
    {
        return $this->view( 'pages.dashboard.settings.general', [
            'title'     =>      __( 'General Settings' ),
            'description'   =>  __( 'Configure the general settings of the application.' )
        ]);
    }

    public function invoiceSettings()
    {
        return $this->view( 'pages.dashboard.settings.general', [
            'title'     =>      __( 'Invoices Settings' ),
            'description'   =>  __( 'Configure the invoice settings.' )
        ]);
    }

    public function ordersSettings()
    {
        return $this->view( 'pages.dashboard.settings.general', [
            'title'     =>      __( 'Orders Settings' ),
            'description'   =>  __( 'Configure the orders settings.' )
        ]);
    }

    public function posSettings()
    {
        return $this->view( 'pages.dashboard.settings.general', [
            'title'     =>      __( 'POS Settings' ),
            'description'   =>  __( 'Configure the pos settings.' )
        ]);
    }

    public function providersSettings()
    {
        return $this->view( 'pages.dashboard.settings.general', [
            'title'     =>      __( 'Providers Settings' ),
            'description'   =>  __( 'Configure the providers settings.' )
        ]);
    }

    public function reportsSettings()
    {
        return $this->view( 'pages.dashboard.settings.general', [
            'title'     =>      __( 'Reports Settings' ),
            'description'   =>  __( 'Configure the reports.' )
        ]);
    }

    public function resetSettings()
    {
        return $this->view( 'pages.dashboard.settings.general', [
            'title'     =>      __( 'Reset Settings' ),
            'description'   =>  __( 'Reste the applications' )
        ]);
    }

    public function servicesSettings()
    {
        return $this->view( 'pages.dashboard.settings.general', [
            'title'     =>      __( 'Services Settings' ),
            'description'   =>  __( 'Configure the services settings.' )
        ]);
    }
    
    public function storesSettings()
    {
        return $this->view( 'pages.dashboard.settings.general', [
            'title'     =>      __( 'Stores Settings' ),
            'description'   =>  __( 'Configure the stores settings.' )
        ]);
    }

    public function postSettings( SettingsRequest $request )
    {

    }
}

