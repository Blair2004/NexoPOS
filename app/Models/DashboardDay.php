<?php

namespace App\Models;

use App\Events\DashboardDayAfterCreatedEvent;
use App\Services\DateService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DashboardDay extends NsModel
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

    /**
     * This should retreive the previous DashboardDay instance
     * @todo Maybe there is a better way to do this
     * @param DashboardDay $day
     * @return DashboardDay
     */
    public static function forLastRecentDay( DashboardDay $day )
    {
        $date       =   Carbon::parse( $day->range_starts )->subDay();

        $dashboardDay   =   DashboardDay::from( $date->startOfDay()->toDateTimeString() )
            ->to( $date->endOfDay()->toDateTimeString() )
            ->first();
            
        if ( $dashboardDay instanceof DashboardDay ) {
            return $dashboardDay;
        }

        $previousDashboardDay                   =   new DashboardDay;
        $previousDashboardDay->range_starts     =   $date->startOfDay()->toDateTimeString();
        $previousDashboardDay->range_ends       =   $date->endOfDay()->toDateTimeString();
        $previousDashboardDay->day_of_year      =   $date->dayOfYear;
        $previousDashboardDay->save();

        return $previousDashboardDay;
    }
}
