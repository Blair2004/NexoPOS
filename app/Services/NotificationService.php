<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\support\Str;

class NotificationService
{
    private $title;

    private $description;

    private $dismissable;

    private $url;

    private $identifier;

    private $source;

    private $notification;

    /**
     * @param array $config [ 'title', 'url', 'identifier', 'source', 'dismissable', 'description' ]
     */
    public function create( string|array $title, string $description = '', string $url = '#', ?string $identifier = null, string $source = 'system', bool $dismissable = true )
    {
        if ( is_array( $title ) ) {
            extract( $title );
        }

        if ( $description && $title ) {
            $this->title = $title;
            $this->url = $url ?: '#';
            $this->identifier = $identifier ?? $this->generateRandomIdentifier();
            $this->source = $source ?? 'system';
            $this->dismissable = $dismissable ?? true;
            $this->description = $description;

            return $this;
        }

        throw new Exception( __( 'Missing required parameters to create a notification' ) );
    }

    /**
     * Will dispatch a notification for all the roles
     * that has permissions belonging to the parameter
     */
    public function dispatchForPermissions( array $permissions ): void
    {
        $roles = Role::whereHas( 'permissions', function ( $query ) use ( $permissions ) {
            $query->whereIn( 'namespace', $permissions );
        } )->get();

        $uniqueRoles = $roles->unique( 'id' );

        if ( $uniqueRoles->isEmpty() ) {
            Log::alert( 'A notification was dispatched for permissions that aren\'t assigned.', $permissions );
        }

        $this->dispatchForGroup( $uniqueRoles );
    }

    /**
     * Dispatch notification for a specific
     * users which belong to a user group
     *
     * @param  Role $role
     * @return void
     */
    public function dispatchForGroup( $role )
    {
        if ( is_array( $role ) ) {
            collect( $role )->each( function ( $role ) {
                $this->dispatchForGroup( $role );
            } );
        } elseif ( $role instanceof Collection ) {
            $role->each( function ( $role ) {
                $this->dispatchForGroup( $role );
            } );
        } elseif ( is_string( $role ) ) {
            $roleInstance = Role::namespace( $role );
            $this->dispatchForGroup( $roleInstance );
        } else {
            $role->users->map( function ( $user ) {
                $this->__makeNotificationFor( $user );
            } );
        }
    }

    /**
     * Dispatch notification for specific
     * groups using array of group namespace provided
     */
    public function dispatchForGroupNamespaces( array $namespaces )
    {
        $this->dispatchForGroup( Role::in( $namespaces )->get() );
    }

    private function __makeNotificationFor( $user )
    {
        $this->notification = Notification::identifiedBy( $this->identifier )
            ->for( $user->id )
            ->first();

        /**
         * if a notification with the same identifier
         * has already been issued for the user, we should avoid
         * issuing new notification.
         */
        if ( ! $this->notification instanceof Notification ) {
            $this->notification = new Notification;
            $this->notification->user_id = $user->id;
            $this->notification->title = $this->title;
            $this->notification->description = $this->description;
            $this->notification->dismissable = $this->dismissable;
            $this->notification->source = $this->source;
            $this->notification->url = $this->url;
            $this->notification->identifier = $this->identifier;
            $this->notification->save();
        } else {
            $this->notification->title = $this->title;
            $this->notification->description = $this->description;
            $this->notification->dismissable = $this->dismissable;
            $this->notification->source = $this->source;
            $this->notification->url = $this->url;
            $this->notification->save();
        }
    }

    public function dispatchForUsers( Collection $users )
    {
        $users->map( function ( $user ) {
            $this->__makeNotificationFor( $user );
        } );
    }

    /**
     * gnerate random identifier
     * for a notification.
     */
    public function generateRandomIdentifier()
    {
        $date = app()->make( DateService::class );

        return 'notification-' . Str::random( 10 ) . '-' . $date->format( 'd-m-y' );
    }

    public function deleteHavingIdentifier( $identifier )
    {
        Notification::identifiedBy( $identifier )
            ->get()
            ->each( function ( $notification ) {
                $notification->delete();
            } );
    }

    public function deleteSingleNotification( $id )
    {
        $notification = Notification::find( $id );
        $notification->delete();
    }

    public function deleteNotificationsFor( User $user )
    {
        Notification::for( $user->id )
            ->get()
            ->each( function ( $notification ) {
                $notification->delete();
            } );
    }
}
