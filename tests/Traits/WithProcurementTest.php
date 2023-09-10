<?php

namespace Tests\Traits;

use App\Classes\Currency;
use App\Models\TransactionHistory;
use App\Models\Procurement;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\Provider;
use App\Models\TaxGroup;
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
        $currentExpenseValue = TransactionHistory::where( 'transaction_account_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $procurementsDetails = $testService->prepareProcurement( ns()->date->now(), [
            'general.payment_status' => 'unpaid',
            'general.provider_id' => $provider->id,
            'general.delivery_status' => Procurement::DELIVERED
        ]);

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/procurements', $procurementsDetails );

        $decode = json_decode( $response->getContent(), true );

        $newExpensevalue = TransactionHistory::where( 'transaction_account_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $procurement = $decode[ 'data' ][ 'procurement' ];

        /**
         * Step 1: there shouldn't be a change on the expenses
         */
        $this->assertTrue( (float) $currentExpenseValue === (float) $newExpensevalue, 'The expenses has changed for an unpaid procurement.' );
        $this->assertTrue( (float) $provider->amount_due !== (float) $provider->fresh()->amount_due, 'The due amount for the provider hasn\'t changed, while it should.' );

        /**
         * Step 2: update the procurement to paid
         */
        $currentTransactionValue = TransactionHistory::where( 'transaction_account_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'GET', 'api/procurements/' . $procurement[ 'id' ] . '/set-as-paid' );

        $response->assertOk();
        $decode = json_decode( $response->getContent(), true );

        $newTransaction = TransactionHistory::where( 'transaction_account_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $existingTransaction = TransactionHistory::where( 'procurement_id', $procurement[ 'id' ] )->first();

        $this->assertEquals( 1, TransactionHistory::where( 'procurement_id', $procurement[ 'id' ] )->count(), 'There is more than 1 cash flow created for the same procurement.' );
        $this->assertEquals( ns()->currency->getRaw( $existingTransaction->value ), ns()->currency->getRaw( $procurement[ 'cost' ] ), 'The cash flow value doesn\'t match the procurement cost.' );
        $this->assertTrue( $existingTransaction instanceof TransactionHistory, 'No cash flow was created after the procurement was marked as paid.' );
        $this->assertTrue( (float) $currentTransactionValue !== (float) $newTransaction, 'The transactions hasn\'t changed for the previously unpaid procurement.' );
    }

    protected function attemptCreateProcurement()
    {
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );

        $currentExpenseValue = TransactionHistory::where( 'transaction_account_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $procurementsDetails = $testService->prepareProcurement( ns()->date->now(), [
            'general.payment_status'    =>  Procurement::PAYMENT_UNPAID,
            'general.delivery_status'   =>  Procurement::PENDING
        ]);

        /**
         * Query: We store the procurement with an unpaid status.
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/procurements', $procurementsDetails );

        $response->assertOk();

        $procurementId  =   $response->json()[ 'data' ][ 'procurement' ][ 'id' ];

        /**
         * Check: at the point, there shouldn't be any expense recorded.
         * The procurement is not paid.
         */
        $existingExpense = TransactionHistory::where( 'procurement_id', $procurementId )->first();
        $this->assertTrue( ! $existingExpense instanceof TransactionHistory, __( 'A cash flow has been created for an unpaid procurement.' ) );

        /**
         * Query: we store the procurement now with a paid status
         */
        $procurementsDetails[ 'general' ][ 'payment_status' ]   =   Procurement::PAYMENT_PAID;
        $procurementsDetails[ 'general' ][ 'delivery_status' ]   =   Procurement::DELIVERED;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/procurements/' . $procurementId, $procurementsDetails );

        $response->assertOk();

        /**
         * We'll proceed to the verification
         * and check if the accounts are valid.
         */
        $responseData = json_decode( $response->getContent(), true );
        $newExpensevalue = TransactionHistory::where( 'transaction_account_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $existingExpense = TransactionHistory::where( 'procurement_id', $responseData[ 'data' ][ 'procurement' ][ 'id' ] )->first();

        $this->assertTrue( $existingExpense instanceof TransactionHistory, __( 'No cash flow were created for the created procurement.' ) );

        $response->assertJson([ 'status' => 'success' ]);

        /**
         * We'll check if the expense value
         * has increased due to the procurement
         */
        $this->assertEquals(
            Currency::raw( $newExpensevalue ),
            Currency::raw( (float) $currentExpenseValue + (float) $responseData[ 'data' ][ 'procurement' ][ 'cost' ] )
        );
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
            'general.payment_status'    =>  Procurement::PAYMENT_PAID,
            'general.delivery_status'   =>  Procurement::DELIVERED,
            'total_products'            =>  5,
            'total_unit_quantities'     =>  1
        ]);

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
        $products       =   $response->json()[ 'data' ][ 'products' ];

        $quantities     =   collect( $products )->map( fn( $product ) => [
            'product_id'    =>  $product[ 'product_id' ],
            'unit_id'       =>  $product[ 'unit_id' ],
            'name'          =>  $product[ 'name' ],
            'quantity'      =>  $product[ 'quantity' ],
            'total_quantity'      =>  $productService->getQuantity(
                product_id: $product[ 'product_id' ],
                unit_id: $product[ 'unit_id' ]
            )
        ]);

        /**
         * lets now delete to see if products
         * was returned
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'DELETE', 'api/procurements/' . $products       =   $response->json()[ 'data' ][ 'procurement' ][ 'id' ] );

        collect( $quantities )->map( function( $product ) use ( $productService ) {
            $actualQuantity = $productService->getQuantity(
                product_id: $product[ 'product_id' ],
                unit_id: $product[ 'unit_id' ]
            );

            $this->assertTrue( $actualQuantity == $product[ 'total_quantity' ] - $product[ 'quantity' ], sprintf(
                'The product "%s" didn\'t has it\'s inventory updated after a procurement deletion to "%s". "%s" is the actual value, "%s" was removed',
                $product[ 'name' ],
                $product[ 'total_quantity' ] - $product[ 'quantity' ],
                $actualQuantity,
                $product[ 'quantity' ]
            ));
        });
    }

    protected function attemptCreateProcurementWithConversion()
    {
        $faker      =   Factory::create();
        $taxType    =   'inclusive';
        $margin     =   10;
        $taxGroup   =   TaxGroup::get()->random();
        
        /**
         * @var TaxService $taxService
         */
        $taxService     =   app()->make( TaxService::class );

        /**
         * @var ProductService $productService
         */
        $productService     =   app()->make( ProductService::class );

        /**
         * @var UnitService $unitService
         */
        $unitService     =   app()->make( UnitService::class );

        $products   =   Product::withStockEnabled()
        ->with( 'unitGroup' )
        ->take(5)
        ->get()
        ->map( function( $product ) {
            return $product->unitGroup->units()->where( 'base_unit', 0 )->limit(1)->get()->map( function( $unit ) use ( $product ) {
                $unitQuantity = $product->unit_quantities->filter( fn( $q ) => (int) $q->unit_id === (int) $unit->id )->first();

                return (object) [
                    'unit' => $unit,
                    'unitQuantity' => $unitQuantity,
                    'product' => $product,
                ];
            });
        })->flatten()->map( function( $data ) use ( $taxService, $taxType, $taxGroup, $margin, $faker ) {
            $quantity = $faker->numberBetween(10, 99);

            $newUnit    =   UnitGroup::with([ 'units' => function( $query ) use ( $data ) {
                $query->whereNotIn( 'id', [ $data->unit->id ]);
            }])->find( $data->unit->group_id )->units->first();

            return [
                'convert_unit_id'   =>  $newUnit->id,
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
        });
        
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );

        $currentExpenseValue = TransactionHistory::where( 'transaction_account_id', ns()->option->get( 'ns_procurement_cashflow_account' ) )->sum( 'value' );
        $procurementsDetails = $testService->prepareProcurement( ns()->date->now(), [
            'general.payment_status'    =>  Procurement::PAYMENT_PAID,
            'general.delivery_status'   =>  Procurement::DELIVERED,
            'products'  =>  $products,
        ]);

        /**
         * Query: We store the procurement with an unpaid status.
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/procurements', $procurementsDetails );

        $response->assertOk();

        $products   =   $response->json()[ 'data' ][ 'products' ];

        collect( $products )->each( function( $product ) use ( $unitService ) {
            $productHistory     =   ProductHistory::where( 'operation_type', ProductHistory::ACTION_CONVERT_OUT )
                ->where( 'procurement_id', $product[ 'procurement_id' ])
                ->where( 'procurement_product_id', $product[ 'id' ])
                ->where( 'quantity', $product[ 'quantity' ] )
                ->first();

            $this->assertTrue( $productHistory instanceof ProductHistory, 'No product history was created after the conversion.' );

            /**
             * check if correct unit was received by the destination unit
             */
            $destinationQuantity    =   $unitService->getConvertedQuantity(
                from: Unit::find( $product[ 'unit_id' ] ),
                to: Unit::find( $product[ 'convert_unit_id' ] ),
                quantity: $product[ 'quantity' ]
            );

            $destinationHistory     =   ProductHistory::where( 'operation_type', ProductHistory::ACTION_CONVERT_IN )
                ->where( 'procurement_id', $product[ 'procurement_id' ])
                ->where( 'procurement_product_id', $product[ 'id' ])
                ->where( 'unit_id', $product[ 'convert_unit_id' ])
                ->where( 'quantity', $destinationQuantity )
                ->first();

            $this->assertTrue( $destinationHistory instanceof ProductHistory, 'No product history was created after the conversion.' );
        });
    }
}
