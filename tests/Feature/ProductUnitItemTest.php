<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Services\UnitService;
use Tests\TestCase;

class ProductUnitItemTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_unit_conversion(): void
    {
        /**
         * @var UnitService $unitService
         */
        $unitService = app()->make( UnitService::class );

        $quantity = 5;
        $result = 10;

        $from = new Unit;
        $from->value = 12;

        $to = new Unit;
        $to->value = 6;

        $partA = $unitService->getConvertedQuantity(
            from: $from,
            to: $to,
            quantity: $quantity
        );

        $partB = (float) ( ( $from->value * $quantity ) / $to->value );

        $this->assertTrue(
            $partA === $partB
        );
    }

    public function test_purchase_price_unit()
    {
        /**
         * @var UnitService $unitService
         */
        $unitService = app()->make( UnitService::class );
        $purchasePrice = 100;
        $partB = (float) 50;

        $from = new Unit;
        $from->value = 12;

        $to = new Unit;
        $to->value = 6;

        $purchasePrice = 100;

        $partA = $unitService->getPurchasePriceFromUnit(
            purchasePrice: $purchasePrice,
            from: $from,
            to: $to
        );

        $this->assertTrue(
            $partA === $partB
        );
    }
}
