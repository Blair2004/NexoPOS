<?php

namespace App\Services;

use App\Classes\Hook;
use App\Enums\NotificationsEnum;
use App\Exceptions\NotEnoughPermissionException;
use App\Exceptions\NotFoundException;
use App\Jobs\CheckTaskSchedulingConfigurationJob;
use App\Models\Migration;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
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
        public MathService $math,
        public EnvEditor $envEditor,
        public MediaService $mediaService,
    ) {
        // ...
    }

    /**
     * Returns a route to which apply
     * the filter "ns-route".
     */
    public function route( string $route, array $params = [] ): string
    {
        return Hook::filter( 'ns-route', false, $route, $params ) ?: route( $route, $params );
    }

    /**
     * Returns a route name to which apply
     * the filter "ns-route-name".
     */
    public function routeName( string $name ): string
    {
        return Hook::filter( 'ns-route-name', $name );
    }

    /**
     * Returns a filtred URL to which
     * apply the filter "ns-url" hook.
     */
    public function url( ?string $url = null ): string
    {
        return url( Hook::filter( 'ns-url', $url ) );
    }

    /**
     * Returns a filtred URL to which
     * apply the filter "ns-url" hook.
     */
    public function asset( string $url ): string
    {
        return url( Hook::filter( 'ns-asset', $url ) );
    }

    /**
     * check if a use is allowed to
     * access a page or trigger an error. This should not be used
     * on middleware or controller constructor.
     */
    public function restrict( $permissions, $message = '' ): void
    {
        if ( is_array( $permissions ) ) {
            $passed = collect( $permissions )->filter( function ( $permission ) {
                if ( is_bool( $permission ) ) {
                    return $permission;
                } else {
                    return $this->allowedTo( $permission );
                }
            } )->count() === count( $permissions );
        } elseif ( is_string( $permissions ) ) {
            $passed = $this->allowedTo( $permissions );
        } elseif ( is_bool( $permissions ) ) {
            $passed = $permissions;
        }

        if ( ! $passed ) {
            throw new NotEnoughPermissionException( $message ?:
                sprintf(
                    __( 'You do not have enough permissions to perform this action.' ) . '<br>' . __( 'Required permissions: %s' ),
                    is_string( $permissions ) ? $permissions : implode( ', ', $permissions )
                )
            );
        }
    }

    /**
     * Will return the logged user details
     * that are actually fillable to avoid exposing any sensitive information.
     */
    public function getUserDetails(): Collection
    {
        return collect( ( new User )->getFillable() )->mapWithKeys( fn( $key ) => [ $key => Auth::user()->$key ] );
    }

    /**
     * Will determine if a user is allowed
     * to perform a specific action (using a permission)
     */
    public function allowedTo( array|string $permissions ): bool
    {
        if ( is_array( $permissions ) ) {
            return Gate::any( $permissions );
        }

        return Gate::allows( $permissions );
    }

    /**
     * check if the logged user has a specific role.
     */
    public function hasRole( string $roleNamespace ): bool
    {
        return Auth::user()
            ->roles()
            ->get()
            ->filter( fn( $role ) => $role->namespace === $roleNamespace )->count() > 0;
    }

    /**
     * clear missing migration files
     * from migrated files.
     */
    public function purgeMissingMigrations(): void
    {
        $migrations = collect( Migration::get() )
            ->map( function ( $migration ) {
                return $migration->migration;
            } );

        $rawFiles = collect( Storage::disk( 'ns' )
            ->allFiles( 'database/migrations' ) );

        $files = $rawFiles->map( function ( $file ) {
            $details = pathinfo( $file );

            return $details[ 'filename' ];
        } );

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
     */
    public function isProduction(): bool
    {
        return ! is_file( base_path( 'public/hot' ) );
    }

    /**
     * Simplify the manifest to return
     * only the files to use.
     */
    public function simplifyManifest(): Collection
    {
        $manifest = json_decode( file_get_contents( base_path( 'public/build/manifest.json' ) ), true );

        return collect( $manifest )
            ->mapWithKeys( fn( $value, $key ) => [ $key => asset( 'build/' . $value[ 'file' ] ) ] )
            ->filter( function ( $element ) {
                $info = pathinfo( $element );

                return $info[ 'extension' ] === 'css';
            } );
    }

    /**
     * Some features must be disabled
     * if the jobs aren't configured correctly.
     */
    public function canPerformAsynchronousOperations(): bool
    {
        $lastUpdate = Carbon::parse( ns()->option->get( 'ns_jobs_last_activity', false ) );

        if ( $lastUpdate->diffInMinutes( ns()->date->now() ) > 60 || ! ns()->option->get( 'ns_jobs_last_activity', false ) ) {
            return false;
        }

        return true;
    }

    /**
     * Check if the tasks scheduling is configured or
     * will emit a notification to help fixing it.
     */
    public function checkTaskSchedulingConfiguration(): void
    {
        if ( ns()->option->get( 'ns_jobs_last_activity', false ) === false ) {
            /**
             * @var NotificationsEnum;
             */
            $this->emitNotificationForTaskSchedulingMisconfigured();

            /**
             * force dispatching the job
             * to force check the tasks status.
             */
            CheckTaskSchedulingConfigurationJob::dispatch();
        } else {
            /**
             * @var DateService
             */
            $date = app()->make( DateService::class );
            $lastUpdate = Carbon::parse( ns()->option->get( 'ns_jobs_last_activity' ) );

            if ( $lastUpdate->diffInMinutes( $date->now() ) > 60 ) {
                $this->emitNotificationForTaskSchedulingMisconfigured();

                /**
                 * force dispatching the job
                 * to force check the tasks status.
                 */
                CheckTaskSchedulingConfigurationJob::dispatch();
            }
        }
    }

    /**
     * Register the available permissions when
     * the app is installed as valid gates.
     */
    public function registerGatePermissions(): void
    {
        /**
         * We'll define gate by using all available permissions.
         * Those will be cached to avoid unecessary db calls when testing
         * wether the user has the permission or not.
         */
        if ( Helper::installed() ) {
            Permission::get()->each( function ( $permission ) {
                if ( ! Gate::has( $permission->namespace ) ) {
                    Gate::define( $permission->namespace, function ( User $user ) use ( $permission ) {
                        $permissions = Cache::remember( 'ns-all-permissions-' . $user->id, 3600, function () use ( $user ) {
                            return $user->roles()
                                ->with( 'permissions' )
                                ->get()
                                ->map( fn( $role ) => $role->permissions->map( fn( $permission ) => $permission->namespace ) )
                                ->flatten();
                        } )->toArray();

                        return in_array( $permission->namespace, $permissions );
                    } );
                }
            } );
        }
    }

    /**
     * This will update the last time
     * the cron has been active
     */
    public function setLastCronActivity(): void
    {
        /**
         * @var NotificationService
         */
        $notification = app()->make( NotificationService::class );
        $notification->deleteHavingIdentifier( NotificationsEnum::NSCRONDISABLED );

        ns()->option->set( 'ns_cron_last_activity', ns()->date->toDateTimeString() );
    }

    /**
     * Will check if the cron has been active recently
     * and delete a ntoification that has been generated for that.
     */
    public function checkCronConfiguration(): void
    {
        if ( ns()->option->get( 'ns_cron_last_activity', false ) === false ) {
            $this->emitCronMisconfigurationNotification();
        } else {
            /**
             * @var DateService
             */
            $date = app()->make( DateService::class );
            $lastUpdate = Carbon::parse( ns()->option->get( 'ns_cron_last_activity' ) );

            if ( $lastUpdate->diffInMinutes( $date->now() ) > 60 ) {
                $this->emitCronMisconfigurationNotification();
            }
        }
    }

    public function checkSymbolicLinks(): void
    {
        if ( ! file_exists( public_path( 'storage' ) ) ) {
            $notification = Notification::where( 'identifier', NotificationsEnum::NSSYMBOLICLINKSMISSING )
                ->first();

            if ( ! $notification instanceof Notification ) {
                ns()->option->set( 'ns_has_symbolic_links_missing_notifications', true );

                $notification = app()->make( NotificationService::class );
                $notification->create( [
                    'title' => __( 'Symbolic Links Missing' ),
                    'identifier' => NotificationsEnum::NSSYMBOLICLINKSMISSING,
                    'source' => 'system',
                    'url' => 'https://my.nexopos.com/en/documentation/troubleshooting/broken-media-images?utm_source=nexopos&utm_campaign=warning&utm_medium=app',
                    'description' => __( 'The Symbolic Links to the public directory is missing. Your medias might be broken and not display.' ),
                ] )->dispatchForGroup( Role::namespace( Role::ADMIN ) );
            }
        } else {
            /**
             * We should only perform this if we have reason to believe
             * there is some records, to avoid the request triggered for no reason.
             */
            if ( ns()->option->get( 'ns_has_symbolic_links_missing_notifications' ) ) {
                Notification::where( 'identifier', NotificationsEnum::NSSYMBOLICLINKSMISSING )->delete();
            }
        }
    }

    /**
     * Emit a notification when Cron aren't
     * correctly configured.
     */
    private function emitCronMisconfigurationNotification(): void
    {
        $notification = app()->make( NotificationService::class );
        $notification->create( [
            'title' => __( 'Cron Disabled' ),
            'identifier' => NotificationsEnum::NSCRONDISABLED,
            'source' => 'system',
            'url' => 'https://my.nexopos.com/en/documentation/troubleshooting/workers-or-async-requests-disabled?utm_source=nexopos&utm_campaign=warning&utm_medium=app',
            'description' => __( "Cron jobs aren't configured correctly on NexoPOS. This might restrict necessary features. Click here to learn how to fix it." ),
        ] )->dispatchForGroup( Role::namespace( Role::ADMIN ) );
    }

    /**
     * Emit a notification when workers aren't
     * correctly configured.
     */
    private function emitNotificationForTaskSchedulingMisconfigured(): void
    {
        $notification = app()->make( NotificationService::class );
        $notification->create( [
            'title' => __( 'Task Scheduling Disabled' ),
            'identifier' => NotificationsEnum::NSWORKERDISABLED,
            'source' => 'system',
            'url' => 'https://my.nexopos.com/en/documentation/troubleshooting/workers-or-async-requests-disabled?utm_source=nexopos&utm_campaign=warning&utm_medium=app',
            'description' => __( 'NexoPOS is unable to schedule background tasks. This might restrict necessary features. Click here to learn how to fix it.' ),
        ] )->dispatchForGroup( Role::namespace( Role::ADMIN ) );
    }

    public function getValidAuthor()
    {
        if ( Auth::check() ) {
            return Auth::id();
        }

        if ( App::runningInConsole() ) {
            $firstAdministrator = User::where( 'active', true )->
                whereRelation( 'roles', 'namespace', Role::ADMIN )->first();

            return $firstAdministrator->id;
        }
    }

    /**
     * Get the asset file name from the manifest.json file of a module in Laravel.
     *
     * @param  int         $moduleId
     * @return string|null
     *
     * @throws NotFoundException
     */
    public function moduleViteAssets( string $fileName, $moduleId ): string
    {
        $moduleService = app()->make( ModulesService::class );
        $module = $moduleService->get( $moduleId );

        if ( empty( $module ) ) {
            throw new NotFoundException(
                sprintf(
                    __( 'The requested module %s cannot be found.' ),
                    $moduleId
                )
            );
        }

        $ds = DIRECTORY_SEPARATOR;

        $possiblePaths = [
            rtrim( $module['path'], $ds ) . $ds . 'Public' . $ds . 'build' . $ds . '.vite' . $ds . 'manifest.json',
            rtrim( $module['path'], $ds ) . $ds . 'Public' . $ds . 'build' . $ds . 'manifest.json',
        ];

        $assets = collect( [] );
        $errors = [];

        foreach ( $possiblePaths as $manifestPath ) {
            if ( ! file_exists( $manifestPath ) ) {
                $errors[] = $manifestPath;

                continue;
            }

            $manifestArray = json_decode( file_get_contents( $manifestPath ), true );

            if ( ! isset( $manifestArray[ $fileName ] ) ) {
                throw new NotFoundException(
                    sprintf(
                        __( 'the requested file "%s" can\'t be located inside the manifest.json for the module %s.' ),
                        $fileName,
                        $module[ 'name' ]
                    )
                );
            }

            /**
             * checks if a css file is declared as well
             */
            $jsUrl = asset( 'modules/' . strtolower( $moduleId ) . '/build/' . $manifestArray[ $fileName ][ 'file' ] ) ?? null;

            if ( ! empty( $manifestArray[ $fileName ][ 'css' ] ) ) {
                $assets = collect( $manifestArray[ $fileName ][ 'css' ] )->map( function ( $url ) use ( $moduleId ) {
                    return '<link rel="stylesheet" href="' . asset( 'modules/' . strtolower( $moduleId ) . '/build/' . $url ) . '"/>';
                } );
            }

            $assets->prepend( '<script type="module" src="' . $jsUrl . '"></script>' );
        }

        if ( count( $errors ) === count( $possiblePaths ) ) {
            throw new NotFoundException(
                sprintf(
                    __( 'The manifest file for the module %s wasn\'t found on all possible directories: %s.' ),
                    $module[ 'name' ],
                    collect( $errors )->join( ', ' ),
                )
            );
        }

        return $assets->flatten()->join( '' );
    }
}
