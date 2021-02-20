<?php
namespace App\Services;

use App\Enums\NotificationsEnums;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\support\Str;
use Exception;
use Illuminate\Support\Collection;

class NotificationService 
{
    private $title;
    private $description;
    private $dismissable;
    private $url;
    private $identifier;
    private $source;
    
    public function create( $config ) 
    {
        extract( $config );

        if ( $description && $title ) {
            $this->title        =   $title;
            $this->url          =   $url ?: '#';
            $this->identifier   =   $identifier ?? $this->generateRandomIdentifier();
            $this->source       =   $source ?? 'system';
            $this->dismissable  =   $dismissable ?? true;
            $this->description  =   $description;
            
            return $this;
        }

        throw new Exception( __( 'Missing required parameters to create a notification' ) );
    }

    /**
     * Dispatch notification for a specific
     * users which belong to a user group
     * @param Role $role
     * @return void
     */
    public function dispatchForGroup( $role )
    {
        if ( is_array( $role ) ) {
            collect( $role )->each( function( $role ) {
                $this->dispatchForGroup( $role );
            });
        } else if ( $role instanceof Collection ) {
            $role->each( function( $role ) {
                $this->dispatchForGroup( $role );
            });
        } else {
            $role->users->map( function( $user ) {
                $this->__makeNotificationFor( $user );
            });
        }
    }

    /**
     * Dispatch notification for specific
     * groups using array of group namespace provided
     * @param array $namespaces
     */
    public function dispatchForGroupNamespaces( array $namespaces )
    {
        $this->dispatchForGroup( Role::in( $namespaces )->get() );
    }

    private function __makeNotificationFor( $user )
    {
        $notification                   =   Notification::identifiedBy( $this->identifier )
            ->for( $user->id )
            ->first();

        /**
         * if a notification with the same identifier
         * has already been issued for the user, we should avoid
         * issuing new notification.
         */
        if ( ! $notification instanceof Notification ) {
            $notification                   =   new Notification;
            $notification->user_id          =   $user->id;
            $notification->title            =   $this->title;
            $notification->description      =   $this->description;
            $notification->dismissable      =   $this->dismissable;
            $notification->source           =   $this->source;
            $notification->url              =   $this->url;
            $notification->identifier       =   $this->identifier;
            $notification->save();
        } else {
            $notification->title            =   $this->title;
            $notification->description      =   $this->description;
            $notification->dismissable      =   $this->dismissable;
            $notification->source           =   $this->source;
            $notification->url              =   $this->url;
            $notification->save();
        }
    }

    public function dispatchForUsers( Collection $users ) 
    {
        $users->map( function( $user ) {
            $this->__makeNotificationFor( $user );
        });
    }

    /**
     * gnerate random identifier
     * for a notification.
     */
    public function generateRandomIdentifier()
    {
        $date       =   app()->make( DateService::class );

        return 'notification-' . Str::random( 10 ) . '-' . $date->format( 'd-m-y' );
    }

    public function deleteHavingIdentifier( $identifier )
    {
        $notification   =   Notification::identifiedBy( $identifier )
            ->get()
            ->map( fn( $notification ) => $notification->delete() );
    }

    public function deleteSingleNotification( $id )
    {
        Notification::find( $id )->delete();
    }

    public function deleteNotificationsFor( User $user )
    {
        Notification::for( $user->id )->delete();
    }
}