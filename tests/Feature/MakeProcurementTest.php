<?php

namespace Tests\Feature;

use App\Models\Procurement;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Role;
use App\Models\TaxGroup;
use App\Services\CurrencyService;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

class MakeProcurementTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

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
        $unitQuantity   =   $product->unit_quantities->random();
        $margin         =   25;

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/procurements', [
                'name'                  =>  sprintf( __( 'Sample Procurement %s' ), Str::random(5) ),
                'general'   =>  [
                    'provider_id'           =>  Provider::get()->random()->id,
                    'payment_status'        =>  Procurement::PAYMENT_PAID,
                    'delivery_status'       =>  Procurement::DELIVERED,
                    'author'                =>  Auth::id(),
                    'automatic_approval'    =>  1
                ], 
                'products'  =>  [
                    [
                        'product_id'            =>  $product->id,
                        'gross_purchase_price'  =>  15,
                        'net_purchase_price'    =>  16,
                        'purchase_price'        =>  $taxService->getTaxGroupComputedValue( 
                            $taxType, 
                            $taxGroup, 
                            $unitQuantity->sale_price - $taxService->getPercentageOf(
                                $unitQuantity->sale_price,
                                $margin
                            ) 
                        ),
                        'quantity'              =>  250,
                        'tax_group_id'          =>  $taxGroup->id,
                        'tax_type'              =>  $taxType,
                        'tax_value'             =>  $taxService->getTaxGroupVatValue( 
                            $taxType, 
                            $taxGroup, 
                            $unitQuantity->sale_price - $taxService->getPercentageOf(
                                $unitQuantity->sale_price,
                                $margin
                            ) 
                        ),
                        'total_purchase_price'  =>  $taxService->getTaxGroupComputedValue( 
                            $taxType, 
                            $taxGroup, 
                            $unitQuantity->sale_price - $taxService->getPercentageOf(
                                $unitQuantity->sale_price,
                                $margin
                            ) 
                        ) * 250,
                        'unit_id'               =>  $unitQuantity->unit_id,
                    ]
                ]
            ]);

        $response->assertJson([ 'status' => 'success' ]);
    }
}
