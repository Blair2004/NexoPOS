<?php

namespace App\Models;

use App\Events\DashboardDayAfterCreatedEvent;
use App\Events\DashboardDayAfterUpdatedEvent;
use App\Services\DateService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property float          $day_expenses
 * @property int            $day_of_year
 * @property \Carbon\Carbon $range_ends
 */
class DashboardDay extends NsModel
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [ 'range_starts', 'range_ends', 'day_of_year' ];

    protected $table = 'nexopos_' . 'dashboard_days';

    protected $dispatchEvents = [
        'created' => DashboardDayAfterCreatedEvent::class,
        'updated' => DashboardDayAfterUpdatedEvent::class,
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
        $date = app()->make( DateService::class );

        return DashboardDay::firstOrCreate( [
            'range_starts' => $date->copy()->startOfDay()->toDateTimeString(),
            'range_ends' => $date->copy()->endOfDay()->toDateTimeString(),
            'day_of_year' => $date->dayOfYear,
        ] );
    }

    public static function forDayBefore( $day ): DashboardDay
    {
        $date = app()->make( DateService::class );
        $startRange = $date->copy()->subDays( $day )->startOfDay()->toDateTimeString();
        $endRange = $date->copy()->subDays( $day )->endOfDay()->toDateTimeString();

        return DashboardDay::from( $startRange )
            ->to( $endRange )
            ->first();
    }

    /**
     * This should retrieve the previous DashboardDay instance
     *
     * @todo Maybe there is a better way to do this
     *
     * @return DashboardDay
     */
    public static function forLastRecentDay( DashboardDay $day )
    {
        $date = Carbon::parse( $day->range_starts )->subDay();

        return DashboardDay::firstOrCreate( [
            'range_starts' => $date->startOfDay()->toDateTimeString(),
            'range_ends' => $date->endOfDay()->toDateTimeString(),
            'day_of_year' => $date->dayOfYear,
        ] );
    }
}
