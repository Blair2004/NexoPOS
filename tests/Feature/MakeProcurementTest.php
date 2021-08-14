<?php

namespace Tests\Feature;

use App\Models\CashFlow;
use App\Models\Procurement;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Role;
use App\Models\TaxGroup;
use App\Services\CurrencyService;
use App\Services\TaxService;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;
use Faker\Factory;

class MakeProcurementTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateProcurement()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $faker          =   Factory::create();
        $product        =   Product::withStockEnabled()->get()->random();

        /**
         * @var TaxService
         */
        $taxService     =   app()->make( TaxService::class );

        /**
         * @var CurrencyService
         */
        $currencyService     =   app()->make( CurrencyService::class );

        $taxType        =   Arr::random([ 'inclusive', 'exclusive' ]);
        $taxGroup       =   TaxGroup::get()->random();
        $margin         =   25;

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/procurements', [
                'name'                  =>  sprintf( __( 'Sample Procurement %s' ), Str::random(5) ),
                'general'   =>  [
                    'provider_id'           =>  Provider::get()->random()->id,
                    'payment_status'        =>  Procurement::PAYMENT_PAID,
                    'delivery_status'       =>  Procurement::DELIVERED,
                    'author'                =>  Auth::id(), // @todo is that required
                    'automatic_approval'    =>  1
                ], 
                'products'  =>  Product::withStockEnabled()
                    ->with( 'unitGroup' )
                    ->get()
                    ->map( function( $product ) {
                    return $product->unitGroup->units->map( function( $unit ) use ( $product ) {
                        $unitQuantity       =   $product->unit_quantities->filter( fn( $q ) => ( int ) $q->unit_id === ( int ) $unit->id )->first();

                        return ( object ) [
                            'unit'      =>  $unit,
                            'unitQuantity'  =>  $unitQuantity,
                            'product'   =>  $product
                        ];
                    });
                })->flatten()->map( function( $data ) use ( $taxService, $taxType, $taxGroup, $margin, $faker ) {

                    $quantity   =   $faker->numberBetween(800,1500);

                    return [
                        'product_id'            =>  $data->product->id,
                        'gross_purchase_price'  =>  15,
                        'net_purchase_price'    =>  16,
                        'purchase_price'        =>  $taxService->getTaxGroupComputedValue( 
                            $taxType, 
                            $taxGroup, 
                            $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                                $data->unitQuantity->sale_price,
                                $margin
                            )
                        ),
                        'quantity'              =>  $quantity,
                        'tax_group_id'          =>  $taxGroup->id,
                        'tax_type'              =>  $taxType,
                        'tax_value'             =>  $taxService->getTaxGroupVatValue( 
                            $taxType, 
                            $taxGroup, 
                            $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                                $data->unitQuantity->sale_price,
                                $margin
                            ) 
                        ),
                        'total_purchase_price'  =>  $taxService->getTaxGroupComputedValue( 
                            $taxType, 
                            $taxGroup, 
                            $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                                $data->unitQuantity->sale_price,
                                $margin
                            ) 
                        ) * $quantity,
                        'unit_id'               =>  $data->unit->id,
                    ];
                })
            ]);

        $responseData       =   json_decode( $response->getContent(), true );
        $response->dump();
        $existingExpense    =   CashFlow::where( 'procurement_id', $responseData[ 'data' ][ 'procurement' ][ 'id' ] )->first();

        $this->assertTrue( $existingExpense instanceof CashFlow, __( 'No cash flow were created for the created procurement.' ) );

        $response->assertJson([ 'status' => 'success' ]);
    }
}
