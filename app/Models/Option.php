<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Option extends NsModel
{
    use HasFactory;
    
    protected $table = 'nexopos_options';
    protected $key;
    
    //
    public function scopeKey( $query, $key )
    {
        return $query->where( 'key', $key )->first();
    }

    /**
     * Get All keys
     * @param string key
     * @return array
    **/

    public function scopeAllkeys( $query, $key )
    {
        return $query->where( 'key', $key )->get();
    }
}
