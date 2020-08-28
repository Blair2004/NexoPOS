<?php
namespace App\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Services\Helpers\App;
use Illuminate\Support\Facades\Auth;

class CoreService
{
    public function __construct()
    {
        $this->currency     =   app()->make( CurrencyService::class );
    }

    public function installed()
    {
        return Helper::installed();
    }

    /**
     * check if a use is allowed to
     * access a page or trigger an error. This should not be used
     * on middleware or controller constructor.
     */
    public function restrict( $permissions, $message = '' )
    {
        $passed     =   false;

        collect( $permissions )->each( function( $permission ) use ( &$passed ) {
            $userPermissionsNamespaces    =   collect( Auth::user()->role->permissions )
                ->map( fn( $permission ) => $permission->namespace )
                ->toArray();

            $passed     =   in_array( $permission, $userPermissionsNamespaces );
        });

        if ( ! $passed ) {
            throw new NotEnoughPermissionException( $message ?: __( 'Your don\'t have enough permission to see this page.' ) );
        }
    }    
}