<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\OrderPayment;
use App\Models\OrderProductRefund;
use App\Models\Product;
use App\Models\Role;
use App\Services\CurrencyService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderRefundTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testRefund()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        /**
         * @var CurrencyService
         */
        $currency       =   app()->make( CurrencyService::class );
        
        $firstFetchCustomer     =   Customer::first();        
        $firstFetchCustomer->save();

        $product        =   Product::withStockEnabled()->with( 'unit_quantities' )->first();
        $shippingFees   =   150;
        $discountRate   =   3.5;
        $products       =   [
            [
                'product_id'            =>  $product->id,
                'quantity'              =>  5,
                'unit_price'            =>  $product->unit_quantities[0]->sale_price,
                'unit_quantity_id'      =>  $product->unit_quantities[0]->id,
            ]
        ];

        $subtotal   =   collect( $products )->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();
        $netTotal   =   $subtotal   +   $shippingFees;

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id'           =>  $firstFetchCustomer->id,
                'type'                  =>  [ 'identifier' => 'takeaway' ],
                'discount_type'         =>  'percentage',
                'discount_percentage'   =>  $discountRate,
                'addresses'             =>  [
                    'shipping'          =>  [
                        'name'          =>  'First Name Delivery',
                        'surname'       =>  'Surname',
                        'country'       =>  'Cameroon',
                    ],
                    'billing'          =>  [
                        'name'          =>  'EBENE Voundi',
                        'surname'       =>  'Antony Hervé',
                        'country'       =>  'United State Seattle',
                    ]
                ],
                'subtotal'              =>  $subtotal,
                'shipping'              =>  $shippingFees,
                'products'              =>  $products,
                'payments'              =>  [
                    [
                        'identifier'    =>  OrderPayment::PAYMENT_CASH,
                        'value'         =>  $netTotal
                    ]
                ]
            ]);
        
        
            $response->assertJson([
            'status'    =>  'success'
        ]);

        $responseData           =   json_decode( $response->getContent(), true );
        
        $secondFetchCustomer    =   $firstFetchCustomer->fresh();

        if ( $currency->define( $secondFetchCustomer->purchases_amount )
            ->subtractBy( $responseData[ 'data' ][ 'order' ][ 'tendered' ] )
            ->getRaw() != $currency->getRaw( $firstFetchCustomer->purchases_amount ) ) {
            throw new Exception( 
                sprintf(
                    __( 'The purchase amount hasn\'t been updated correctly. Expected %s, got %s' ),
                    $secondFetchCustomer->purchases_amount - ( float ) $responseData[ 'data' ][ 'order' ][ 'tendered' ],
                    $firstFetchCustomer->purchases_amount
                )
            );
        }

        /**
         * We'll keep original products amounts and quantity
         * this means we're doing a full refund of price and quantities
         */
        $responseData[ 'data' ][ 'order' ][ 'products' ][0][ 'condition' ]      =   OrderProductRefund::CONDITION_DAMAGED;
        $responseData[ 'data' ][ 'order' ][ 'products' ][0][ 'quantity' ]       =   1;
        $responseData[ 'data' ][ 'order' ][ 'products' ][0][ 'description' ]    =   __( 'The product wasn\'t properly manufactured, causing external damage to the device during the shipment.' );

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders/' . $responseData[ 'data' ][ 'order' ][ 'id' ] . '/refund', [
                'payment'   =>  [
                    'identifier'    =>  'account-payment',
                ],
                'total'     =>  $responseData[ 'data' ][ 'order' ][ 'total' ],
                'products'  =>  $responseData[ 'data' ][ 'order' ][ 'products' ],
            ]);

        $responseData           =   json_decode( $response->getContent(), true );

        $thirdFetchCustomer      =   $secondFetchCustomer->fresh();

        if ( 
            $currency->define( $thirdFetchCustomer->purchases_amount )
                ->additionateBy( $responseData[ 'data' ][ 'orderRefund' ][ 'total' ] )
                ->getRaw() !== $currency->getRaw( $secondFetchCustomer->purchases_amount ) ) {

            throw new Exception( 
                sprintf(
                    __( 'The purchase amount hasn\'t been updated correctly. Expected %s, got %s' ),
                    $secondFetchCustomer->purchases_amount,
                    $thirdFetchCustomer->purchases_amount + ( float ) $responseData[ 'data' ][ 'orderRefund' ][ 'total' ]
                )
            );
        }

        /**
         * let's check if an expense has been created accordingly
         */
        $expenseCategory    =   ExpenseCategory::find( ns()->option->get( 'ns_sales_refunds_cashflow_account' ) );

        if ( ! $expenseCategory instanceof ExpenseCategory ) {
            throw new Exception( __( 'An expense hasn\'t been created after the refund.' ) );
        }

        $expense    =   $expenseCategory->expensesHistory()->orderBy( 'id', 'desc' )->first();
        if ( ( float ) $expense->getRawOriginal( 'value' ) != ( float ) $responseData[ 'data' ][ 'orderRefund' ][ 'total' ] ) {
            throw new Exception( __( 'The expense created after the refund doesn\'t match the product value.' ) );
        }  
        
        $response->assertJson([
            'status'    =>  'success'
        ]);
    }
}
