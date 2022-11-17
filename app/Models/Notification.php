<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_notifications';

    protected $casts    =   [
        'created_at'    =>  'datetime:Y-m-d H:i:s',
        'updated_at'    =>  'datetime:Y-m-d H:i:s',
    ];

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
