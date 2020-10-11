<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DashboardDay extends Model
{
    use HasFactory;

    protected $table    =   'nexopos_' . 'dashboard_day';

    public function scopeFrom( $query, $param )
    {
        return $query->where( 'range_starts', '>=', $param );
    }

    public function scopeTo( $query, $param )
    {
        return $query->where( 'range_ends', '<=', $param );
    }
}
