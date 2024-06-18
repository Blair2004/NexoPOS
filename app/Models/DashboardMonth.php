<?php

namespace App\Models;

use App\Events\DashboardMonthAfterCreatedEvent;
use App\Events\DashboardMonthAfterUpdatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property float          $total_expenses
 * @property int            $month_of_year
 * @property \Carbon\Carbon $updated_at
 */
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
