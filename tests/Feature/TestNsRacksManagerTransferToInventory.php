<?php

namespace Tests\Feature;

use App\Models\Procurement;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Role;
use App\Models\TaxGroup;
use App\Services\CurrencyService;
use App\Services\ProcurementService;
use App\Services\ProductService;
use App\Services\TaxService;
use Exception;
use Faker\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\NsMultiStore\Models\Store;
use Modules\NsRacksManager\Models\Rack;
use Modules\NsRacksManager\Models\RackProductQuantity;
use Modules\NsRacksManager\Services\RacksServices;
use Tests\TestCase;

class TestNsRacksManagerTransferToInventory extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $faker = Factory::create();

        $store = Store::find( 4 );
        Store::switchTo( $store );

        /**
         * @var RacksServices
         */
        $rackService = app()->make( RacksServices::class );

        /**
         * @var ProductService
         */
        $productService = app()->make( ProductService::class );

        /**
         * @var ProcurementService
         */
        $procurementService = app()->make( ProcurementService::class );

        /**
         * @var TaxService
         */
        $taxService = app()->make( TaxService::class );

        /**
         * @var CurrencyService
         */
        $currencyService = app()->make( CurrencyService::class );

        $taxType = Arr::random( [ 'inclusive', 'exclusive' ] );
        $taxGroup = TaxGroup::get()->random();
        $margin = 25;

        $rack = Rack::first();

        $rackProduct = RackProductQuantity::where( 'quantity', '>', 2 )
            ->where( 'rack_id', $rack->id )
            ->first();

        if ( ! $rackProduct instanceof RackProductQuantity ) {
            throw new Exception( __( 'Unable to find a valid Rack Product.' ) );
        }

        /**
         * Step 1 : make a procurement
         */
        $procurementService->create( [
            'name' => sprintf( __( 'Sample Procurement %s' ), Str::random( 5 ) ),
            'general' => [
                'provider_id' => Provider::get()->random()->id,
                'payment_status' => Procurement::PAYMENT_PAID,
                'delivery_status' => Procurement::DELIVERED,
                'automatic_approval' => 1,
            ],
            'products' => Product::whereIn( 'id', [ $rackProduct->product_id ] )
                ->with( 'unitGroup' )
                ->get()
                ->map( function ( $product ) {
                    return $product->unitGroup->units->map( function ( $unit ) use ( $product ) {
                        return (object) [
                            'unit' => $unit,
                            'unitQuantity' => $product->unit_quantities->filter( fn( $q ) => $q->unit_id === $unit->id )->first(),
                            'product' => $product,
                        ];
                    } );
                } )->flatten()->map( function ( $data ) use ( $taxService, $taxType, $taxGroup, $margin, $faker, $rack ) {
                    return [
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
                        'quantity' => $faker->numberBetween( 10, 50 ),
                        'rack_id' => $rack->id, // we've defined the rack id
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
                        ) * 250,
                        'unit_id' => $data->unit->id,
                    ];
                } ),
        ] );

        $processedQuantity = 2;

        $oldQuantity = $productService->getUnitQuantity( $rackProduct->product_id, $rackProduct->unit_id );

        /**
         * attempt to move product
         * from rack to inventory.
         */
        $response = $rackService->moveToInventory( $rackProduct, $processedQuantity );

        $newQuantity = $productService->getUnitQuantity( $rackProduct->product_id, $rackProduct->unit_id );

        if ( (int) abs( $oldQuantity->quantity - $newQuantity->quantity ) !== $processedQuantity ) {
            throw new Exception(
                sprintf(
                    __( 'The old quantity (%s) minus the new quantity (%s) doesn\'t gives the expected value (%s)' ),
                    $oldQuantity->quantity,
                    $newQuantity->quantity,
                    $processedQuantity
                )
            );
        }
    }
}
