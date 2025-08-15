<?php

namespace App\Models;

use App\Events\NotificationCreatedEvent;
use App\Events\NotificationUpdatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

/**
 * @property int            $id
 * @property int            $user_id
 * @property string         $source
 * @property string         $description
 * @property bool           $dismissable
 * @property \Carbon\Carbon $updated_at
 */
class Notification extends NsModel
{
    use HasFactory, Notifiable;

    protected $table = 'nexopos_notifications';

    protected $dispatchesEvents = [
        'created' => NotificationCreatedEvent::class,
        'updated' => NotificationUpdatedEvent::class,
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * We saving, we want to make sure the "actions" property is actually a json and not an array.
     * We also want to make sure while retreiving, the JSON is converted to an array.
     */
    public static function boot()
    {
        parent::boot();

        static::saving( function ( $notification ) {
            $notification->actions = json_encode( $notification->actions );
        } );

        static::retrieved( function ( $notification ) {
            $notification->actions = json_decode( $notification->actions, true );
        } );
    }

    public function user()
    {
        return $this->belongsTo( User::class );
    }

    public function scopeIdentifiedBy( $query, $identifier )
    {
        return $query->where( 'identifier', $identifier );
    }

    public function scopeFor( $query, $user_id )
    {
        return $query->where( 'user_id', $user_id );
    }
}
