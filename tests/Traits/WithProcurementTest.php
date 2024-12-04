<?php

namespace Tests\Traits;

use App\Models\Procurement;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\Provider;
use App\Models\TaxGroup;
use App\Models\TransactionHistory;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Services\ProductService;
use App\Services\TaxService;
use App\Services\TestService;
use App\Services\UnitService;
use Faker\Factory;

trait WithProcurementTest
{
    protected function attemptCreateAnUnpaidProcurement()
    {
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );

        $provider = Provider::get()->random();
        $procurementsDetails = $testService->prepareProcurement( ns()->date->now(), [
            'general.payment_status' => 'unpaid',
            'general.provider_id' => $provider->id,
            'general.delivery_status' => Procurement::DELIVERED,
            'total_products' => 10,
        ] );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/procurements', $procurementsDetails );

        /**
         * Step 1: there shouldn't be a change on the expenses
         */
        $this->assertTrue( (float) $provider->amount_due !== (float) $provider->fresh()->amount_due, 'The due amount for the provider hasn\'t changed, while it should.' );

        return $response->json();
    }

    protected function attemptPayUnpaidProcurement( $procurement_id )
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'GET', 'api/procurements/' . $procurement_id . '/set-as-paid' );

        $response->assertOk();

        return $response->json();
    }

    protected function attemptCreateProcurement()
    {
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );

        $procurementsDetails = $testService->prepareProcurement( ns()->date->now(), [
            'general.payment_status' => Procurement::PAYMENT_UNPAID,
            'general.delivery_status' => Procurement::PENDING,
            'total_products' => 10,
        ] );

        /**
         * Query: We store the procurement with an unpaid status.
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/procurements', $procurementsDetails );

        $response->assertOk();

        $procurementId = $response->json()[ 'data' ][ 'procurement' ][ 'id' ];

        /**
         * Check: at the point, there shouldn't be any expense recorded.
         * The procurement is not paid.
         */
        $existingExpense = TransactionHistory::where( 'procurement_id', $procurementId )->first();
        $this->assertTrue( ! $existingExpense instanceof TransactionHistory, __( 'A cash flow has been created for an unpaid procurement.' ) );

        /**
         * Query: we store the procurement now with a paid status
         */
        $procurementsDetails[ 'general' ][ 'payment_status' ] = Procurement::PAYMENT_PAID;
        $procurementsDetails[ 'general' ][ 'delivery_status' ] = Procurement::DELIVERED;

        /**
         * Query: We store the procurement with an unpaid status.
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/procurements/' . $procurementId, $procurementsDetails );

        $response->assertOk();

        /**
         * We'll proceed to the verification
         * and check if the accounts are valid.
         */
        $response->assertJson( [ 'status' => 'success' ] );

        return $response->json();
    }

    protected function attemptDeleteProcurementWithConvertedProducts()
    {
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );

        /**
         * @var ProductService
         */
        $productService = app()->make( ProductService::class );

        /**
         * @var UnitService
         */
        $unitService = app()->make( UnitService::class );

        $procurementsDetails = $testService->prepareProcurement( ns()->date->now(), [
            'general.payment_status' => Procurement::PAYMENT_PAID,
            'general.delivery_status' => Procurement::DELIVERED,
            'total_products' => 5,
            'total_unit_quantities' => 1,
        ] );

        $product = $procurementsDetails[ 'products' ][0];
        $unit = Unit::with( 'group' )->find( $product[ 'unit_id' ] );

        // lets get the group excluding the base unit
        $group = $unit->group()->with( [
            'units' => function ( $query ) {
                $query->where( 'base_unit', 0 );
            },
        ] )->first();

        // We assign a different not base unit to the product to ensure
        // while converting, it creates quantities on the base unit
        $product[ 'unit_id' ] = $group->units->last()->id;
        $baseUnit = Unit::where( 'base_unit', true )->where( 'group_id', $group->id )->first();

        // This is the quantity of the product using
        // the base unit as reference
        $baseQuantity = $productService->getQuantity(
            product_id: $product[ 'product_id' ],
            unit_id: $baseUnit->id
        );

        $OtherQuantity = $productService->getQuantity(
            product_id: $product[ 'product_id' ],
            unit_id: $product[ 'unit_id' ]
        );

        // We want to convert this into the base unit
        $product[ 'convert_unit_id' ] = $baseUnit->id;

        $procurementsDetails[ 'products' ] = [$product];

        // Now we'll save the requests and check first if the conversion
        // succeeded. Then we'll make sure to delete and see if the stock is updated
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/procurements', $procurementsDetails );

        $response->assertOk();
        $procurement = $response->json()[ 'data' ][ 'procurement' ];

        // The conversion doesn't changes the quantity of the product/unit
        // which is the source of the conversion. The quantity should therefore remain unchanged.
        $newOtherQuantity = $productService->getQuantity(
            product_id: $product[ 'product_id' ],
            unit_id: $product[ 'unit_id' ]
        );

        $this->assertSame( $OtherQuantity, $newOtherQuantity, 'The quantity of the source of the conversion has changed. ' );

        // The quantity of the destination product must change
        // after a conversion.
        $newBaseQuantity = $productService->getQuantity(
            product_id: $product[ 'product_id' ],
            unit_id: $baseUnit->id
        );

        $receivedQuantity = $unitService->getConvertedQuantity(
            from: Unit::find( $product[ 'unit_id' ] ),
            to: $baseUnit,
            quantity: $product[ 'quantity' ]
        );

        $this->assertTrue(
            (float) $receivedQuantity === ns()->currency->define( $newBaseQuantity )->subtractBy( $baseQuantity )->toFloat(),
            sprintf(
                'The destination hasn\'t receieve the quantity %s it should have received from conversion',
                $receivedQuantity
            )
        );

        $this->assertTrue( $baseQuantity < $newBaseQuantity, 'The destination product inventory hasn\'t changed with the conversion.' );

        // Let's check if after a conversion there is an history created
        // an history necessarilly result into a stock. There is no need to test that here
        $productHistory = ProductHistory::where( 'product_id', $product[ 'product_id' ] )
            ->where( 'unit_id', $product[ 'unit_id' ] )
            ->where( 'procurement_id', $procurement[ 'id' ] )
            ->where( 'operation_type', ProductHistory::ACTION_CONVERT_OUT )
            ->first();

        $this->assertTrue( $productHistory instanceof ProductHistory );

        // We'll now check if there has been an incoming conversion
        // again it's not necessary to test the quantity as the history results in a stock adjustment
        $productHistory = ProductHistory::where( 'product_id', $product[ 'product_id' ] )
            ->where( 'unit_id', $baseUnit->id )
            ->where( 'procurement_id', $procurement[ 'id' ] )
            ->where( 'operation_type', ProductHistory::ACTION_CONVERT_IN )
            ->first();

        $this->assertTrue( $productHistory instanceof ProductHistory );

        // Now we'll delete the procurement to see wether the product
        // that was converted are removed from inventory
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'DELETE', 'api/procurements/' . $procurement[ 'id' ] );

        // We test here if the base item which as received stock
        // loose it because of the procurement deletion
        // The quantity of the destination product must change
        // after a conversion and return to the original value
        $lastNewBaseQuantity = $productService->getQuantity(
            product_id: $product[ 'product_id' ],
            unit_id: $baseUnit->id
        );

        $this->assertSame( $baseQuantity, $lastNewBaseQuantity, 'The product doesn\'t have the original quantity after deletion' );
    }

    protected function attemptDeleteProcurementWithId( $id )
    {
        /**
         * lets now delete to see if products
         * was returned
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'DELETE', 'api/procurements/' . $id );

        $response->assertOk();
    }

    protected function attemptDeleteProcurement()
    {
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );

        /**
         * @var ProductService
         */
        $productService = app()->make( ProductService::class );

        $procurementsDetails = $testService->prepareProcurement( ns()->date->now(), [
            'general.payment_status' => Procurement::PAYMENT_PAID,
            'general.delivery_status' => Procurement::DELIVERED,
            'total_products' => 5,
            'total_unit_quantities' => 1,
        ] );

        /**
         * We need to retreive the quantities for the products
         * that will be procured
         */
        $initialQuantities = collect( $procurementsDetails[ 'products' ] )->map( function ( $product ) use ( $productService ) {
            return [
                'product_id' => $product[ 'product_id' ],
                'unit_id' => $product[ 'unit_id' ],
                'procured_quantity' => $product[ 'quantity' ],
                'current_quantity' => $productService->getQuantity(
                    product_id: $product[ 'product_id' ],
                    unit_id: $product[ 'unit_id' ]
                ),
            ];
        } );

        /**
         * Query: We store the procurement with an unpaid status.
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/procurements', $procurementsDetails );

        $response->assertOk();

        /**
         * identifying the products and retreive
         * the current quantities
         */
        $products = $response->json()[ 'data' ][ 'products' ];

        /**
         * We'll not compare the previous quantity with the new quantity and see if
         * that's the result of the addition of the previous quantity plus the procured quantity
         */
        collect( $products )->map( function ( $product ) use ( $productService, $initialQuantities ) {
            $currentQuantity = $productService->getQuantity(
                product_id: $product[ 'product_id' ],
                unit_id: $product[ 'unit_id' ]
            );

            $initialQuantity = collect( $initialQuantities )->filter( fn( $q ) => (int) $q[ 'product_id' ] === (int) $product[ 'product_id' ] && (int) $q[ 'unit_id' ] === (int) $product[ 'unit_id' ] )->first();

            $this->assertSame(
                ns()->currency->define( $currentQuantity )->toFloat(),
                ns()->currency->define( $initialQuantity[ 'current_quantity' ] )->additionateBy( $initialQuantity[ 'procured_quantity' ] )->toFloat(),
                sprintf(
                    'The product "%s" didn\'t has it\'s inventory updated after a procurement. "%s" is the actual value, "%s" was added and "%s" was expected.',
                    $product[ 'name' ],
                    $currentQuantity,
                    $initialQuantity[ 'procured_quantity' ],
                    $initialQuantity[ 'current_quantity' ] + $initialQuantity[ 'procured_quantity' ]
                )
            );
        } );

        $quantities = collect( $products )->map( fn( $product ) => [
            'product_id' => $product[ 'product_id' ],
            'unit_id' => $product[ 'unit_id' ],
            'name' => $product[ 'name' ],
            'procured_quantity' => $product[ 'quantity' ],
            'current_quantity' => $productService->getQuantity(
                product_id: $product[ 'product_id' ],
                unit_id: $product[ 'unit_id' ]
            ),
        ] );

        /**
         * lets now delete to see if products
         * was returned
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'DELETE', 'api/procurements/' . $response->json()[ 'data' ][ 'procurement' ][ 'id' ] );

        collect( $quantities )->map( function ( $product ) use ( $productService, $initialQuantities ) {
            $currentQuantity = $productService->getQuantity(
                product_id: $product[ 'product_id' ],
                unit_id: $product[ 'unit_id' ]
            );

            $actualProduct = collect( $initialQuantities )->filter( fn( $q ) => (int) $q[ 'product_id' ] === (int) $product[ 'product_id' ] && (int) $q[ 'unit_id' ] === (int) $product[ 'unit_id' ] )->first();

            $this->assertSame(
                ns()->currency->define( $currentQuantity )->toFloat(),
                ns()->currency->define( $product[ 'current_quantity' ] )->subtractBy( $product[ 'procured_quantity' ] )->toFloat(),
                sprintf(
                    'The product "%s" didn\'t has it\'s inventory updated after a procurement deletion. "%s" is the actual value, "%s" was removed and "%s" was expected.',
                    $product[ 'name' ],
                    $currentQuantity,
                    $product[ 'procured_quantity' ],
                    $product[ 'current_quantity' ] - $product[ 'procured_quantity' ]
                )
            );
        } );
    }

    protected function attemptCreateProcurementWithConversion()
    {
        $faker = Factory::create();
        $taxType = 'inclusive';
        $margin = 10;
        $taxGroup = TaxGroup::get()->random();

        /**
         * @var TaxService $taxService
         */
        $taxService = app()->make( TaxService::class );

        /**
         * @var ProductService $productService
         */
        $productService = app()->make( ProductService::class );

        /**
         * @var UnitService $unitService
         */
        $unitService = app()->make( UnitService::class );

        $products = Product::withStockEnabled()
            ->with( 'unitGroup' )
            ->take( 5 )
            ->get()
            ->map( function ( $product ) {
                return $product->unitGroup->units()->where( 'base_unit', 0 )->limit( 1 )->get()->map( function ( $unit ) use ( $product ) {
                    $unitQuantity = $product->unit_quantities->filter( fn( $q ) => (int) $q->unit_id === (int) $unit->id )->first();

                    return (object) [
                        'unit' => $unit,
                        'unitQuantity' => $unitQuantity,
                        'product' => $product,
                    ];
                } );
            } )->flatten()->map( function ( $data ) use ( $taxService, $taxType, $taxGroup, $margin, $faker ) {
                $quantity = $faker->numberBetween( 10, 99 );

                $newUnit = UnitGroup::with( [ 'units' => function ( $query ) use ( $data ) {
                    $query->whereNotIn( 'id', [ $data->unit->id ] );
                }] )->find( $data->unit->group_id )->units->first();

                return [
                    'convert_unit_id' => $newUnit->id,
                    'product_id' => $data->product->id,
                    'gross_purchase_price' => 15,
                    'net_purchase_price' => 16,
                    'purchase_price' => $taxService->getTaxGroupComputedValue(
                        $taxType,
                        $taxGroup,
                        $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                            $data->unitQuantity->sale_price,
                            $margin
                        )
                    ),
                    'quantity' => $quantity,
                    'tax_group_id' => $taxGroup->id,
                    'tax_type' => $taxType,
                    'tax_value' => $taxService->getTaxGroupVatValue(
                        $taxType,
                        $taxGroup,
                        $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                            $data->unitQuantity->sale_price,
                            $margin
                        )
                    ),
                    'total_purchase_price' => $taxService->getTaxGroupComputedValue(
                        $taxType,
                        $taxGroup,
                        $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                            $data->unitQuantity->sale_price,
                            $margin
                        )
                    ) * $quantity,
                    'unit_id' => $data->unit->id,
                ];
            } );

        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );

        $procurementsDetails = $testService->prepareProcurement( ns()->date->now(), [
            'general.payment_status' => Procurement::PAYMENT_PAID,
            'general.delivery_status' => Procurement::DELIVERED,
            'products' => $products,
        ] );

        /**
         * Query: We store the procurement with an unpaid status.
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/procurements', $procurementsDetails );

        $response->assertOk();

        $products = $response->json()[ 'data' ][ 'products' ];

        collect( $products )->each( function ( $product ) use ( $unitService ) {
            $productHistory = ProductHistory::where( 'operation_type', ProductHistory::ACTION_CONVERT_OUT )
                ->where( 'procurement_id', $product[ 'procurement_id' ] )
                ->where( 'procurement_product_id', $product[ 'id' ] )
                ->where( 'quantity', $product[ 'quantity' ] )
                ->first();

            $this->assertTrue( $productHistory instanceof ProductHistory, 'No product history was created after the conversion.' );

            /**
             * check if correct unit was received by the destination unit
             */
            $destinationQuantity = $unitService->getConvertedQuantity(
                from: Unit::find( $product[ 'unit_id' ] ),
                to: Unit::find( $product[ 'convert_unit_id' ] ),
                quantity: $product[ 'quantity' ]
            );

            $destinationHistory = ProductHistory::where( 'operation_type', ProductHistory::ACTION_CONVERT_IN )
                ->where( 'procurement_id', $product[ 'procurement_id' ] )
                ->where( 'procurement_product_id', $product[ 'id' ] )
                ->where( 'unit_id', $product[ 'convert_unit_id' ] )
                ->where( 'quantity', $destinationQuantity )
                ->first();

            $this->assertTrue( $destinationHistory instanceof ProductHistory, 'No product history was created after the conversion.' );
        } );
    }
}
