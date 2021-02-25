<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Http\Requests\SettingsRequest;
use App\Services\CrudService;
use App\Services\Options;
use App\Services\SettingsPage;
use Exception;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class SettingsController extends DashboardController
{
    private $taxService;
    private $options;

    public function __construct(
        Options $options
    )
    {
        $this->options  =   $options;

        parent::__construct();
    }

    public function getSettings( $identifier )
    {
        ns()->restrict([ 'manage.options' ]);
        
        switch( $identifier ) {
            case 'customers'; return $this->customersSettings(); break;
            case 'general'; return $this->generalSettings(); break;
            case 'invoices'; return $this->invoiceSettings(); break;
            case 'orders'; return $this->ordersSettings(); break;
            case 'pos'; return $this->posSettings(); break;
            case 'supplies-deliveries'; return $this->suppliesDeliveries(); break;
            case 'reports'; return $this->reportsSettings(); break;
            case 'service-providers'; return $this->serviceProviders(); break;
            case 'invoice-settings'; return $this->invoiceSettings(); break;

            case 'reset'; return $this->resetSettings(); break;
            case 'notifications'; return $this->notificationsSettings(); break;
            case 'workers'; return $this->workersSettings(); break;
            default : return abort( 404, __( 'Settings Page Not Found' ) );break;
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
        return $this->view( 'pages.dashboard.settings.customers', [
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

    public function notificationsSettings()
    {
        return $this->view( 'pages.dashboard.settings.notifications', [
            'title'     =>      __( 'Notifications Settings' ),
            'description'   =>  __( 'Configure the notifications settings of the application.' )
        ]);
    }

    public function invoiceSettings()
    {
        return $this->view( 'pages.dashboard.settings.invoices', [
            'title'     =>      __( 'Invoices Settings' ),
            'description'   =>  __( 'Configure the invoice settings.' )
        ]);
    }

    public function ordersSettings()
    {
        return $this->view( 'pages.dashboard.settings.orders', [
            'title'     =>      __( 'Orders Settings' ),
            'description'   =>  __( 'Configure the orders settings.' )
        ]);
    }

    public function posSettings()
    {
        return $this->view( 'pages.dashboard.settings.pos', [
            'title'     =>      __( 'POS Settings' ),
            'description'   =>  __( 'Configure the pos settings.' )
        ]);
    }

    public function suppliesDeliveries()
    {
        return $this->view( 'pages.dashboard.settings.supplies-deliveries', [
            'title'     =>      __( 'Supplies & Deliveries Settings' ),
            'description'   =>  __( 'Configure the supplies and deliveries settings.' )
        ]);
    }

    public function reportsSettings()
    {
        return $this->view( 'pages.dashboard.settings.reports', [
            'title'     =>      __( 'Reports Settings' ),
            'description'   =>  __( 'Configure the reports.' )
        ]);
    }

    public function resetSettings()
    {
        /**
         * @temp
         */
        if ( Auth::user()->role->namespace !== 'admin' ) {
            throw new Exception( __( 'Access Denied' ) );
        }

        return $this->view( 'pages.dashboard.settings.reset', [
            'title'     =>      __( 'Reset Settings' ),
            'description'   =>  __( 'Reset the data and enable demo.' )
        ]);
    }

    public function serviceProviders()
    {
        return $this->view( 'pages.dashboard.settings.service-providers', [
            'title'     =>      __( 'Services Providers Settings' ),
            'description'   =>  __( 'Configure the services providers settings.' )
        ]);
    }

    public function workersSettings()
    {
        return $this->view( 'pages.dashboard.settings.workers', [
            'title'     =>      __( 'Workers Settings' ),
            'description'   =>  __( 'Configure the workers settings.' )
        ]);
    }

    public function saveSettingsForm( SettingsRequest $request, $identifier )
    {
        ns()->restrict([ 'manage.options' ]);

        $resource   =   Hook::filter( 'ns.settings', false, $identifier );
        
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

