<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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

        if ( Helper::installed() ) {
            $this->timezone = $timezone;
            $this->options = app()->make( Options::class );
    
            if ( Auth::check() ) {
                $language  =   Auth::user()->attribute->language ?: $this->options->get( 'ns_store_language', 'light' );
            } else {
                $language  =   $this->options->get( 'ns_store_language', 'en' );
            }
    
            $longForm   =   $this->getLongLocaleCode( $language );
    
            $this->locale( $longForm );
        }
    }

    /**
     * Return the long locale form
     * for a short version provided
     */
    public function getLongLocaleCode( string $locale ): string
    {
        return match( $locale ) {
            'fr'    =>  'fr_FR',
            'en'    =>  'en_US',
            'es'    =>  'es_ES',
            'it'    =>  'it_IT',
            'ar'    =>  'ar_SA',
            'pt'    =>  'pt_PT',
            'tr'    =>  'tr_TR',
            'vi'    =>  'vi_VN',
            default =>  'en_US',
        };
    }

    public function define( $time, $timezone = 'Europe/London' )
    {
        $this->__construct( $time, $timezone );
    }

    /**
     * Get the defined date format.
     */
    public function getFormatted( string $date, string $mode = 'full' ): string
    {
        switch ( $mode ) {
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
     */
    public function getNow(): DateService
    {
        return $this->now( $this->timezone );
    }

    /**
     * Return a formatted string the current date/time
     * usign a defined format (full or short)
     */
    public function getNowFormatted( string $mode = 'full' ): string
    {
        switch ( $mode ) {
            case 'short':
                return $this->format( $this->options->get( 'ns_date_format', 'Y-m-d' ) );
                break;
            case 'full':
                return $this->format( $this->options->get( 'ns_datetime_format', 'Y-m-d H:i:s' ) );
                break;
        }
    }

    /**
     * Get days as an array between two dates.
     */
    public function getDaysInBetween( Carbon $startRange, Carbon $endRange ): array
    {
        if ( $startRange->lessThan( $endRange ) && $startRange->diffInDays( $endRange ) >= 1 ) {
            $days = [];

            do {
                $days[] = $startRange->copy();
                $startRange->addDay();
            } while ( ! $startRange->isSameDay( $endRange ) );

            $days[] = $endRange->copy();

            return $days;
        }

        return [];
    }
}
