<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_notifications';

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