<?php
namespace App\Models;

abstract class UserScope extends NsModel
{
    /**
     * Get customer using email
     *
     * @param Query
     * @param string email
     */
    public function scopeByEmail( $query, $email )
    {
        return $query->where( 'email', $email );
    }

    /**
     * Get customers that are currently active.
     */
    public function scopeActive( $query, $active = true )
    {
        return $query->where( 'active', $active );
    }

    /**
     * Get customers that are currently active.
     */
    public function scopeBanned( $query, $active = true )
    {
        return $query->where( 'banned', $active );
    }

    /**
     * Get customers that are currently active.
     */
    public function scopeNotBanned( $query, $active = false )
    {
        return $query->where( 'banned', $active );
    }

    /**
     * get customers from groups
     */
    public function scopeFromGroup( $query, $index )
    {
        return $query->where( 'parent_id', $index );
    }
}