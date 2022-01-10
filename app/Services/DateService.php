<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DateService extends Carbon
{
    /**
     * @var Options
     */
    private $options;
    private $timezone;

    public function __construct( $time = 'now', $timezone = 'Europe/London' )
    {
        parent::__construct( $time, $timezone );

        $this->timezone     =   $timezone;        
        $this->options      =   app()->make( Options::class );
    }

    public function define( $time, $timezone = 'Europe/London' )
    {
        $this->__construct( $time, $timezone );
    }

    /**
     * Get the defined date format
     * @param string $date
     * @param string $mode
     * @return string formatted string
     */
    public function getFormatted( $date, $mode = 'full' )
    {
        switch( $mode ) {
            case 'short':
                return $this->parse( $date )->format( $this->options->get( 'ns_date_format', 'Y-m-d' ) );
            break;
            case 'full':
                return $this->parse( $date )->format( $this->options->get( 'ns_datetime_format', 'Y-m-d H:i:s' ) );
            break;
        }
    }

    /**
     * Get the actual date time instance
     * based on the provided timezone
     * @return DateService $date
     */
    public function getNow()
    {
        return $this->now( $this->timezone );
    }

    /**
     * Return a formatted string the current date/time
     * usign a defined format (full or short)
     * @return string
     */
    public function getNowFormatted( $mode = 'full' )
    {
        switch( $mode ) {
            case 'short':
                return $this->now( $this->timezone )->format( $this->options->get( 'ns_date_format', 'Y-m-d' ) );
            break;
            case 'full':
                return $this->now( $this->timezone )->format( $this->options->get( 'ns_datetime_format', 'Y-m-d H:i:s' ) );
            break;
        }
    }

    /**
     * get days between two dates
     * @param Carbon $startRange
     * @param Carbon $endRange
     * @return array[Carbon]
     */
    public function getDaysInBetween( $startRange, $endRange )
    {
        if ( $startRange->lessThan( $endRange ) && $startRange->diffInDays( $endRange ) >= 1 ) {
            $days       =   [];
    
            do {
                $days[]     =   $startRange->copy();
                $startRange->addDay();
            } while ( ! $startRange->isSameDay( $endRange ) );
    
            $days[]     =   $endRange->copy();
    
            return $days;
        }

        return [];
    }
}