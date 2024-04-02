<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $user_id
 * @property string         $key
 * @property string         $value
 * @property \Carbon\Carbon $updated_at
 * @property bool           $array
 */
class Option extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_options';

    public $parsed = false;

    protected $key;

    protected $casts = [
        'array' => 'boolean',
        'user_id' => 'integer',
    ];

    public function scopeKey( $query, $key )
    {
        return $query->where( 'key', $key )->first();
    }

    /**
     * Get All keys
     *
     * @param string key
     * @return array
     **/
    public function scopeAllkeys( $query, $key )
    {
        return $query->where( 'key', $key )->get();
    }
}
