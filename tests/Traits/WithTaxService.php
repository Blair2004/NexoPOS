<?php

namespace Tests\Traits;

use App\Services\TaxService;

trait WithTaxService
{
    public function getPercentageOf( $value, $rate )
    {
        /**
         * @var TaxService $taxService
         */
        $taxService = app()->make( TaxService::class );

        return $taxService->getPercentageOf(
            value: $value,
            rate: $rate
        );
    }
}
