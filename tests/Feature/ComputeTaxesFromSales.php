<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\Tax;
use App\Models\TaxGroup;
use App\Services\OrdersService;
use App\Services\TaxService;
use App\Services\TestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComputeTaxesFromSales extends TestCase
{
    const TAX_FLAT          =   'flat_vat';
    const TAX_VARIABLE      =   'variable_vat';
    const TAX_PRODUCTS_VAT  =   'products_vat';
    const TAX_PRODUCTS_VAVT =   'products_variable_vat';

    public function test_product_tax_variable()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );
        
        /**
         * @var OrdersService
         */
        $orderService   =   app()->make( OrdersService::class );
        
        /**
         * @var TaxService
         */
        $taxService   =   app()->make( TaxService::class );
        $taxGroup       =   TaxGroup::with( 'taxes' )->first();

        ns()->option->set( 'ns_pos_vat', self::TAX_PRODUCTS_VAVT );
        ns()->option->set( 'ns_pos_tax_group', $taxGroup->id );
        ns()->option->set( 'ns_pos_tax_type', 'exclusive' );

        $testService    =   new TestService;
        $details        =   $testService->prepareOrder( ns()->date->now(), [
            'tax_group_id'  =>  $taxGroup->id,
            'tax_type'      =>  'exclusive',
            'taxes'         =>  $taxGroup->taxes->map( function( $tax ) {
                $tax->tax_name  =   $tax->name;
                $tax->tax_id    =   $tax->id;
                return $tax;
            })
        ]);

        $this->assertCheck( $details, function( $order ) use ( $orderService, $taxService ) {
            
            $subtotal   =   $taxService->getComputedTaxGroupValue( $order[ 'tax_type' ], $order[ 'tax_group_id' ], $order[ 'subtotal' ] );

            $this->assertEquals( 
                ( float ) $orderService->getOrderProductsTaxes( Order::find( $order[ 'id' ] ) ) + ( float ) $subtotal, 
                ( float ) $order[ 'tax_value' ],
                __( 'The product tax is not valid.' )
            );
        });
    }

    public function test_tax_products_vat()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );
        
        /**
         * @var OrdersService
         */
        $orderService   =   app()->make( OrdersService::class );
        $taxGroup       =   TaxGroup::with( 'taxes' )->first();

        ns()->option->set( 'ns_pos_vat', self::TAX_PRODUCTS_VAT );
        ns()->option->set( 'ns_pos_tax_group', $taxGroup->id );
        ns()->option->set( 'ns_pos_tax_type', 'exclusive' );

        $testService    =   new TestService;
        $details        =   $testService->prepareOrder( ns()->date->now(), [
            'tax_group_id'  =>  $taxGroup->id,
            'tax_type'      =>  'exclusive',
            'taxes'         =>  $taxGroup->taxes->map( function( $tax ) {
                $tax->tax_name  =   $tax->name;
                $tax->tax_id    =   $tax->id;
                return $tax;
            })
        ]);

        $this->assertCheck( $details, function( $order ) use ( $orderService ) {
            $this->assertEquals( 
                ( float ) $orderService->getOrderProductsTaxes( Order::find( $order[ 'id' ] ) ), 
                ( float ) $order[ 'tax_value' ],
                __( 'The product tax is not valid.' )
            );
        });
    }

    public function test_variable_vat()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );
        
        $taxGroup       =   TaxGroup::with( 'taxes' )->first();

        ns()->option->set( 'ns_pos_vat', self::TAX_VARIABLE );
        ns()->option->set( 'ns_pos_tax_group', $taxGroup->id );
        ns()->option->set( 'ns_pos_tax_type', 'exclusive' );

        $testService    =   new TestService;
        $details        =   $testService->prepareOrder( ns()->date->now(), [
            'tax_group_id'  =>  $taxGroup->id,
            'tax_type'      =>  'exclusive',
            'taxes'         =>  $taxGroup->taxes->map( function( $tax ) {
                $tax->tax_name  =   $tax->name;
                $tax->tax_id    =   $tax->id;
                return $tax;
            })
        ]);

        $this->assertCheck( $details );
    }

    public function test_flat_expense()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        
        $taxGroup       =   TaxGroup::with( 'taxes' )->first();

        ns()->option->set( 'ns_pos_vat', self::TAX_FLAT );
        ns()->option->set( 'ns_pos_tax_group', $taxGroup->id );
        ns()->option->set( 'ns_pos_tax_type', 'exclusive' );

        $testService    =   new TestService;
        $details        =   $testService->prepareOrder( ns()->date->now(), [
            'tax_group_id'  =>  $taxGroup->id,
            'tax_type'      =>  'exclusive',
            'taxes'         =>  $taxGroup->taxes->map( function( $tax ) {
                $tax->tax_name  =   $tax->name;
                $tax->tax_id    =   $tax->id;
                return $tax;
            })
        ]);

        $this->assertCheck( $details );
    }

    /**
     * Check the tax calculation
     *
     * @return void
     */
    public function test_inclusive_tax()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );
        
        $taxGroup       =   TaxGroup::with( 'taxes' )->first();
        $testService    =   new TestService;
        $details        =   $testService->prepareOrder( ns()->date->now(), [
            'tax_group_id'  =>  $taxGroup->id,
            'tax_type'      =>  'inclusive',
            'taxes'         =>  $taxGroup->taxes->map( function( $tax ) {
                $tax->tax_name  =   $tax->name;
                $tax->tax_id    =   $tax->id;
                return $tax;
            })
        ]);

        $this->assertCheck( $details );
    }

    /**
     * Check the tax calculation
     *
     * @return void
     */
    public function test_exclusive_tax()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );
        
        $taxGroup       =   TaxGroup::with( 'taxes' )->first();
        $testService    =   new TestService;
        $details        =   $testService->prepareOrder( ns()->date->now(), [
            'tax_group_id'  =>  $taxGroup->id,
            'tax_type'      =>  'exclusive',
            'taxes'         =>  $taxGroup->taxes->map( function( $tax ) {
                $tax->tax_name  =   $tax->name;
                $tax->tax_id    =   $tax->id;
                return $tax;
            })
        ]);

        $this->assertCheck( $details );
    }

    private function assertCheck( $details, callable $callback = null )
    {        
        /**
         * @var TaxService
         */
        $taxService     =   app()->make( TaxService::class );

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', $details );

        $response->assertStatus( 200 );

        $json           =   json_decode( $response->getContent(), true );
        $order          =   $json[ 'data' ][ 'order' ];
        $expectedTax    =   $taxService->getComputedTaxGroupValue( $order[ 'tax_type' ], $order[ 'tax_group_id' ], $order[ 'subtotal' ] );

        if ( $callback === null ) {
            $this->assertEquals( ( float ) $expectedTax, ( float ) $order[ 'tax_value' ], __( 'The computed taxes aren\'t correct.' ) );
        } else {
            $callback( $order );
        }
    }
}
