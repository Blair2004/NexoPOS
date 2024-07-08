<?php

namespace Tests\Traits;

use App\Models\Order;
use App\Models\TaxGroup;
use App\Services\OrdersService;
use App\Services\TaxService;
use App\Services\TestService;

trait WithTaxTest
{
    const TAX_FLAT = 'flat_vat';

    const TAX_VARIABLE = 'variable_vat';

    const TAX_PRODUCTS_VAT = 'products_vat';

    const TAX_PRODUCTS_VAVT = 'products_variable_vat';

    protected function attemptProductTaxVariable()
    {
        /**
         * @var OrdersService
         */
        $orderService = app()->make( OrdersService::class );

        /**
         * @var TaxService
         */
        $taxService = app()->make( TaxService::class );
        $taxGroup = TaxGroup::with( 'taxes' )->first();

        ns()->option->set( 'ns_pos_vat', self::TAX_PRODUCTS_VAVT );
        ns()->option->set( 'ns_pos_tax_group', $taxGroup->id );
        ns()->option->set( 'ns_pos_tax_type', 'exclusive' );

        $testService = new TestService;
        $details = $testService->prepareOrder( ns()->date->now(), [
            'tax_group_id' => $taxGroup->id,
            'tax_type' => 'exclusive',
            'taxes' => $taxGroup->taxes->map( function ( $tax ) {
                $tax->tax_name = $tax->name;
                $tax->tax_id = $tax->id;

                return $tax;
            } ),
        ] );

        $this->assertCheck( $details, function ( $order ) use ( $orderService ) {
            $this->assertEquals(
                (float) $orderService->getOrderProductsTaxes( Order::find( $order[ 'id' ] ) ),
                (float) $order[ 'products_tax_value' ],
                __( 'The product tax is not valid.' )
            );
        } );
    }

    protected function attemptTaxProductVat()
    {
        /**
         * @var OrdersService
         */
        $orderService = app()->make( OrdersService::class );
        $taxGroup = TaxGroup::with( 'taxes' )->first();

        ns()->option->set( 'ns_pos_vat', self::TAX_PRODUCTS_VAT );
        ns()->option->set( 'ns_pos_tax_group', $taxGroup->id );
        ns()->option->set( 'ns_pos_tax_type', 'exclusive' );

        $testService = new TestService;
        $details = $testService->prepareOrder( ns()->date->now(), [
            'tax_group_id' => $taxGroup->id,
            'tax_type' => 'exclusive',
            'taxes' => $taxGroup->taxes->map( function ( $tax ) {
                $tax->tax_name = $tax->name;
                $tax->tax_id = $tax->id;

                return $tax;
            } ),
        ] );

        $this->assertCheck( $details, function ( $order ) use ( $orderService ) {
            $this->assertEquals(
                (float) $orderService->getOrderProductsTaxes( Order::find( $order[ 'id' ] ) ),
                (float) $order[ 'products_tax_value' ],
                __( 'The product tax is not valid.' )
            );
        } );
    }

    protected function attemptFlatExpense()
    {
        $taxGroup = TaxGroup::with( 'taxes' )->first();

        ns()->option->set( 'ns_pos_vat', self::TAX_FLAT );
        ns()->option->set( 'ns_pos_tax_group', $taxGroup->id );
        ns()->option->set( 'ns_pos_tax_type', 'exclusive' );

        $testService = new TestService;
        $details = $testService->prepareOrder( ns()->date->now(), [
            'tax_group_id' => $taxGroup->id,
            'tax_type' => 'exclusive',
            'taxes' => $taxGroup->taxes->map( function ( $tax ) {
                $tax->tax_name = $tax->name;
                $tax->tax_id = $tax->id;

                return $tax;
            } ),
        ] );

        $this->assertCheck( $details );
    }

    protected function attemptInclusiveTax()
    {
        $taxGroup = TaxGroup::with( 'taxes' )->first();
        $testService = new TestService;
        $details = $testService->prepareOrder( ns()->date->now(), [
            'tax_group_id' => $taxGroup->id,
            'tax_type' => 'inclusive',
            'taxes' => $taxGroup->taxes->map( function ( $tax ) {
                $tax->tax_name = $tax->name;
                $tax->tax_id = $tax->id;

                return $tax;
            } ),
        ] );

        $this->assertCheck( $details );
    }

    protected function attemptExclusiveTax()
    {
        $taxGroup = TaxGroup::with( 'taxes' )->first();
        $testService = new TestService;
        $details = $testService->prepareOrder( ns()->date->now(), [
            'tax_group_id' => $taxGroup->id,
            'tax_type' => 'exclusive',
            'taxes' => $taxGroup->taxes->map( function ( $tax ) {
                $tax->tax_name = $tax->name;
                $tax->tax_id = $tax->id;

                return $tax;
            } ),
        ] );

        $this->assertCheck( $details );
    }

    protected function attemptVariableVat()
    {
        $taxGroup = TaxGroup::with( 'taxes' )->first();

        ns()->option->set( 'ns_pos_vat', self::TAX_VARIABLE );
        ns()->option->set( 'ns_pos_tax_group', $taxGroup->id );
        ns()->option->set( 'ns_pos_tax_type', 'exclusive' );

        $testService = new TestService;
        $details = $testService->prepareOrder( ns()->date->now(), [
            'tax_group_id' => $taxGroup->id,
            'tax_type' => 'exclusive',
            'taxes' => $taxGroup->taxes->map( function ( $tax ) {
                $tax->tax_name = $tax->name;
                $tax->tax_id = $tax->id;

                return $tax->toArray();
            } ),
        ] );

        $this->assertCheck( $details );
    }

    private function assertCheck( $details, ?callable $callback = null )
    {
        /**
         * @var TaxService
         */
        $taxService = app()->make( TaxService::class );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/orders', $details );

        $response->assertStatus( 200 );

        $json = json_decode( $response->getContent(), true );
        $order = $json[ 'data' ][ 'order' ];
        $expectedTax = $taxService->getComputedTaxGroupValue( $order[ 'tax_type' ], $order[ 'tax_group_id' ], ns()->currency->define( $order[ 'subtotal' ] )->subtractBy( $order[ 'discount' ] )->toFloat() );

        if ( $callback === null ) {
            $this->assertEquals(
                ns()->currency->define( $expectedTax )->toFloat(),
                ns()->currency->define( $order[ 'tax_value' ] )->toFloat(),
                __( 'The computed taxes aren\'t correct.' )
            );
        } else {
            $callback( $order );
        }
    }

    protected function attemptCreateTaxGroup()
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', '/api/crud/ns.taxes-groups', [
                'name' => __( 'GST' ),
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );
    }

    protected function attemptCreateTax()
    {
        $group = TaxGroup::get()->shuffle()->first();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.taxes', [
                'name' => __( 'SGST' ),
                'general' => [
                    'rate' => 5.5,
                    'tax_group_id' => $group->id,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.taxes', [
                'name' => __( 'CGST' ),
                'general' => [
                    'rate' => 6.5,
                    'tax_group_id' => $group->id,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );
    }
}
