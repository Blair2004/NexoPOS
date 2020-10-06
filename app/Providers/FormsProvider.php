<?php

namespace App\Providers;

use App\Forms\POSAddressesForm;
use App\Forms\ProcurementForm;
use App\Forms\UserProfileForm;
use TorMorten\Eventy\Facades\Events as Hook;
use Illuminate\Support\ServiceProvider;

class FormsProvider extends ServiceProvider
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
        Hook::addFilter( 'ns.forms', function( $class, $identifier ) {
            switch( $identifier ) {
                case 'ns.user-profile': 
                    return new UserProfileForm; 
                break;
                case 'ns.procurement':
                    return new ProcurementForm;
                break;
                case 'ns.pos-addresses':
                    return new POSAddressesForm;
                break;
            }
            return $class;
        }, 10, 2 );
    }
}
