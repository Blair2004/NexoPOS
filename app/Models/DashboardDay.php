<?php

namespace App\Models;

use App\Services\DateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DashboardDay extends Model
{
    use HasFactory;

    public $timestamps  =   false;
    protected $table    =   'nexopos_' . 'dashboard_days';

    public function scopeFrom( $query, $param )
    {
        return $query->where( 'range_starts', '>=', $param );
    }

    public function scopeTo( $query, $param )
    {
        return $query->where( 'range_ends', '<=', $param );
    }

    public static function forToday()
    {
        $date   =   app()->make( DateService::class );
        
        return DashboardDay::from( $date->copy()->startOfDay()->toDateTimeString() )
            ->to( $date->copy()->endOfDay()->toDateTimeString() )
            ->first();
    }

    public static function forDayBefore( $day ): DashboardDay
    {
        $date           =   app()->make( DateService::class );
        $startRange     =   $date->copy()->subDays( $day )->startOfDay()->toDateTimeString();
        $endRange       =   $date->copy()->subDays( $day )->endOfDay()->toDateTimeString();

        return DashboardDay::from( $startRange )
            ->to( $endRange )
            ->first();
    }
}
