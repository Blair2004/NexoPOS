<?php

namespace App\Services;

use App\Classes\Hook;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Migration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CoreService
{
    /**
     * @var bool
     */
    public $isMultistore = false;

    public $storeID;

    /**
     * @var \Modules\NsMultiStore\Services\StoresService
     */
    public $store;

    public function __construct(
        public CurrencyService $currency,
        public UpdateService $update,
        public DateService $date,
        public OrdersService $order,
        public NotificationService $notification,
        public ProcurementService $procurement,
        public Options $option,
        public MathService $math
    ) {
        // ...
    }

    /**
     * Returns a boolean if the system
     * is installed or not. returns "true" if the system is installed
     * and "false" if it's not.
     *
     * @return bool
     */
    public function installed()
    {
        return Helper::installed();
    }

    /**
     * Returns a filtered route to which apply
     * the filter "ns-route".
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function route( $route, $params = [])
    {
        return Hook::filter( 'ns-route', false, $route, $params ) ?: route( $route, $params );
    }

    /**
     * Returns a filtred route name to which apply
     * the filter "ns-route-name".
     *
     * @param string $name
     * @return string $name
     */
    public function routeName( $name )
    {
        return Hook::filter( 'ns-route-name', $name );
    }

    /**
     * Returns a filtred URL to which
     * apply the filter "ns-url" hook.
     *
     * @param string $url
     * @return string $url
     */
    public function url( $url = null )
    {
        return url( Hook::filter( 'ns-url', $url ) );
    }

    /**
     * Returns a filtred URL to which
     * apply the filter "ns-url" hook.
     *
     * @param string $url
     * @return string $url
     */
    public function asset( $url )
    {
        return url( Hook::filter( 'ns-asset', $url ) );
    }

    /**
     * check if a use is allowed to
     * access a page or trigger an error. This should not be used
     * on middleware or controller constructor.
     */
    public function restrict( $permissions, $message = '' )
    {
        $passed = $this->allowedTo( $permissions );

        if ( ! $passed ) {
            throw new NotEnoughPermissionException( $message ?: __( 'Your don\'t have enough permission to see this page.' ) );
        }
    }

    /**
     * Will determine if a user is allowed
     * to perform a specific action (using a permission)
     *
     * @param array $permissions
     * @return boolean;
     */
    public function allowedTo( $permissions ): bool
    {
        $passed = false;

        collect( $permissions )->each( function ( $permission ) use ( &$passed ) {
            $userPermissionsNamespaces = collect( Auth::user()->permissions() )
                ->toArray();

            /**
             * if there is a match with the permission or the provided permission is "true"
             * that causes permission check bypass.
             */
            $passed = in_array( $permission, $userPermissionsNamespaces ) || $permission === true;
        });

        return $passed;
    }

    /**
     * check if the logged user has a specific role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole( $roleNamespace )
    {
        return Auth::user()
            ->roles()
            ->get()
            ->filter( fn( $role ) => $role->namespace === $roleNamespace )->count() > 0;
    }

    /**
     * clear missing migration files
     * from migrated files.
     *
     * @return void
     */
    public function purgeMissingMigrations()
    {
        $migrations = collect( Migration::get() )
            ->map( function ( $migration ) {
                return $migration->migration;
            });

        $rawFiles = collect( Storage::disk( 'ns' )
        ->allFiles( 'database/migrations' ) );

        $files = $rawFiles->map( function ( $file ) {
            $details = pathinfo( $file );

            return $details[ 'filename' ];
        });

        $difference = array_diff(
            $migrations->toArray(),
            $files->toArray()
        );

        foreach ( $difference as $diff ) {
            Migration::where( 'migration', $diff )->delete();
        }
    }

    /**
     * Returns a boolean if the environment is
     * on production mode
     *
     * @return bool
     */
    public function isProduction()
    {
        return in_array( strtolower( env( 'NS_ENV', 'prod' ) ), [ 'prod', 'production' ]);
    }
}
