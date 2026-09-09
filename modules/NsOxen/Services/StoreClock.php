<?php

namespace Modules\NsOxen\Services;

use Carbon\Carbon;
use DateTimeInterface;
use Throwable;

class StoreClock
{
    public function now(): Carbon
    {
        try {
            $now = ns()->date->getNow();

            if ( $now instanceof DateTimeInterface ) {
                return Carbon::instance( $now )->copy();
            }
        } catch ( Throwable ) {
            // Laravel's clock remains available during early bootstrap and isolated tests.
        }

        return Carbon::now( config( 'app.timezone', 'UTC' ) );
    }

    public function normalize( DateTimeInterface|string $date ): Carbon
    {
        $now = $this->now();
        $normalized = $date instanceof DateTimeInterface
            ? Carbon::instance( $date )
            : Carbon::parse( $date, $now->getTimezone() );

        return $normalized->setTimezone( $now->getTimezone() );
    }
}
