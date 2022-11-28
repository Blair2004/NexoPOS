<?php

namespace App\Services;

use App\Classes\Hook;
use App\Enums\NotificationsEnum;
use App\Exceptions\NotEnoughPermissionException;
use App\Jobs\CheckTaskSchedulingConfigurationJob;
use App\Models\Migration;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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
     */
    public function installed(): bool
    {
        return Helper::installed();
    }

    /**
     * Returns a filtered route to which apply
     * the filter "ns-route".
     */
    public function route( string $route, array $params = []): string
    {
        return Hook::filter( 'ns-route', false, $route, $params ) ?: route( $route, $params );
    }

    /**
     * Returns a filtred route name to which apply
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
    public function url( string $url = null ): string
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
        $passed = $this->allowedTo( $permissions );

        if ( ! $passed ) {
            throw new NotEnoughPermissionException( $message ?: __( 'Your don\'t have enough permission to see this page.' ) );
        }
    }

    /**
     * Will return the logged user details
     * that are actually fillable to avoid exposing any sensitive information.
     */
    public function getUserDetails(): Collection
    {
        return collect( ( new User() )->getFillable() )->mapWithKeys( fn( $key ) => [ $key => Auth::user()->$key ] );
    }

    /**
     * Will determine if a user is allowed
     * to perform a specific action (using a permission)
     */
    public function allowedTo( array|string $permissions ): bool
    {
        $passed = false;

        collect( $permissions )->each( function( $permission ) use ( &$passed ) {
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
            ->map( function( $migration ) {
                return $migration->migration;
            });

        $rawFiles = collect( Storage::disk( 'ns' )
        ->allFiles( 'database/migrations' ) );

        $files = $rawFiles->map( function( $file ) {
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
            ->filter( function( $element ) {
                $info = pathinfo( $element );

                return $info[ 'extension' ] === 'css';
            });
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

    /**
     * Emit a notification when Cron aren't
     * correctly configured.
     */
    private function emitCronMisconfigurationNotification(): void
    {
        $notification = app()->make( NotificationService::class );
        $notification->create([
            'title' => __( 'Cron Disabled' ),
            'identifier' => NotificationsEnum::NSCRONDISABLED,
            'source' => 'system',
            'url' => 'https://my.nexopos.com/en/documentation/troubleshooting/workers-or-async-requests-disabled?utm_source=nexopos&utm_campaign=warning&utm_medium=app',
            'description' => __( "Cron jobs aren't configured correctly on NexoPOS. This might restrict necessary features. Click here to learn how to fix it." ),
        ])->dispatchForGroup( Role::namespace( Role::ADMIN ) );
    }

    /**
     * Emit a notification when workers aren't
     * correctly configured.
     */
    private function emitNotificationForTaskSchedulingMisconfigured(): void
    {
        $notification = app()->make( NotificationService::class );
        $notification->create([
            'title' => __( 'Task Scheduling Disabled' ),
            'identifier' => NotificationsEnum::NSWORKERDISABLED,
            'source' => 'system',
            'url' => 'https://my.nexopos.com/en/documentation/troubleshooting/workers-or-async-requests-disabled?utm_source=nexopos&utm_campaign=warning&utm_medium=app',
            'description' => __( 'NexoPOS is unable to schedule background tasks. This might restrict necessary features. Click here to learn how to fix it.' ),
        ])->dispatchForGroup( Role::namespace( Role::ADMIN ) );
    }
}
