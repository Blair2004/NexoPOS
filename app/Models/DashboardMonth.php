<?php

namespace App\Models;

use App\Events\DashboardMonthAfterCreatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DashboardMonth extends NsModel
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'nexopos_' . 'dashboard_months';

    protected $dispatchEvents = [
        'created' => DashboardMonthAfterCreatedEvent::class,
        'updated' => DashboardMonthAfterUpdatedEvent::class,
    ];

    public function scopeFrom( $query, $param )
    {
        return $query->where( 'range_starts', '>=', $param );
    }

    public function scopeTo( $query, $param )
    {
        return $query->where( 'range_ends', '<=', $param );
    }
}
