<?php

namespace App\Models;

use App\Events\DashboardDayAfterCreatedEvent;
use App\Services\DateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DashboardMonth extends NsModel
{
    use HasFactory;

    public $timestamps  =   false;
    protected $table    =   'nexopos_' . 'dashboard_months';

    protected $dispatchEvents   =   [
        'saved'     =>  DashboardMonthAfterCreatedEvent::class,
        'updated'   =>  DashboardMonthAfterCreatedEvent::class,
    ];

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

    /**
     * This should retreive the previous DashboardDay instance
     * @todo Maybe there is a better way to do this
     * @param DashboardDay $day
     * @return DashboardDay
     */
    public static function forLastRecentDay( DashboardDay $day )
    {
        $dashboardDay       =   DashboardDay::get()->filter( function( $dashboard ) use ( $day ) {
            return $dashboard->id < $day->id;
        });

        return $dashboardDay->last();
    }
}
