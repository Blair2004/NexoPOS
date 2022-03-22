<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Exceptions\NotAllowedException;
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
            case 'reports'; return $this->reportsSettings(); break;
            case 'service-providers'; return $this->serviceProviders(); break;
            case 'invoice-settings'; return $this->invoiceSettings(); break;
            case 'expenses-settings'; return $this->expenseSettings(); break;
            case 'reset'; return $this->resetSettings(); break;
            case 'notifications'; return $this->notificationsSettings(); break;
            case 'workers'; return $this->workersSettings(); break;
            case 'accounting'; return $this->accountingSettings(); break;
            case 'about': return $this->aboutSettings(); break;
            default : return $this->handleDefaultSettings( $identifier );break;
        }
    }

    /**
     * Show the about page about the system.
     * Where you'll get details about your setup
     * including PHP version, mysql, etc.
     */
    public function aboutSettings()
    {
        return view( 'pages.dashboard.about', [
            'menus' =>  $this->menuService,
            'title' =>  __( 'About' ),
            'description'   =>  __( 'Details about the environment.' ),
            'details'  =>  [
                __( 'Core Version'  )           =>  config( 'nexopos.version' ),
                __( 'PHP Version' )             =>  phpversion(),
            ],
            'extensions'      =>  [
                __( 'Mb String Enabled' )   =>  extension_loaded( 'mbstring' ),
                __( 'Zip Enabled' )         =>  extension_loaded( 'zip' ),
                __( 'Curl Enabled' )        =>  extension_loaded( 'curl' ),
                __( 'Math Enabled' )        =>  extension_loaded( 'bcmath' ),
                __( 'XML Enabled' )         =>  extension_loaded( 'xml' ),
                __( 'XDebug Enabled' )         =>  extension_loaded( 'xdebug' ),
            ],
            'configurations'     =>      [
                __( 'File Upload Enabled' )     =>  (( bool ) ini_get( 'file_uploads' )) ? __( 'Yes' ) : __( 'No' ),
                __( 'File Upload Size' )        =>  ini_get( 'upload_max_filesize' ),
                __( 'Post Max Size' )           =>  ini_get( 'post_max_size' ),
                __( 'Max Execution Time' )      =>  sprintf( __( '%s Second(s)' ), ini_get( 'max_execution_time' ) ),
                __( 'Memory Limit' )            =>  ini_get( 'memory_limit' ),
            ]        
        ]);
    }

    public function handleDefaultSettings( $identifier )
    {
        $settings       =   Hook::filter( 'ns.settings', false, $identifier );

        if ( $settings instanceof SettingsPage ) {
            return $settings->renderForm();
        }

        return abort( 404, __( 'Settings Page Not Found' ) );
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

    public function expenseSettings()
    {
        return $this->view( 'pages.dashboard.settings.expenses', [
            'title'     =>      __( 'Expenses Settings' ),
            'description'   =>  __( 'Configure the expenses settings of the application.' )
        ]);
    }

    public function notificationsSettings()
    {
        return $this->view( 'pages.dashboard.settings.notifications', [
            'title'     =>      __( 'Notifications Settings' ),
            'description'   =>  __( 'Configure the notifications settings of the application.' )
        ]);
    }

    public function accountingSettings()
    {
        return $this->view( 'pages.dashboard.settings.accounting', [
            'title'     =>      __( 'Accounting Settings' ),
            'description'   =>  __( 'Configure the accounting settings of the application.' )
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
         * Users who can manage options can 
         * reset the system.
         */
        if ( ! Auth::user()->allowedTo([ 'manage.options' ]) ) {
            throw new NotAllowedException( __( 'Access Denied' ) );
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

