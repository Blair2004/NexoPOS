<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Crud\CustomerCrud;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use App\Services\OrdersService;
use App\Services\Options;
use App\Events\ProcurementAfterUpdateEvent;
use App\Models\Order;
use App\Models\Option;
use App\Models\Procurement;
use TorMorten\Eventy\Facades\Events as Hook;

class OrdersController extends DashboardController
{
    /** @var OrdersService */
    private $ordersService;

    /** @var OptionsService */
    private $optionsService;

    private $paymentTypes;

    public function __construct(
        OrdersService $ordersService,
        Options $options
    )
    {
        parent::__construct();

        $this->optionsService       =   $options;
        $this->ordersService        =   $ordersService;

        $this->paymentTypes         =   collect( config( 'nexopos.pos.payments' ) )->map( function( $payment, $index ) {
            return array_merge([
                'selected'  =>  $index === 0,
            ], $payment );
        });
    }

    public function create( Request $request )
    {
        return $this->ordersService->create( $request->all() );
    }

    public function refundOrderProduct( $order_id, $product_id )
    {
        $order      =   $this->ordersService->getOrder( $order_id );
        
        $product    =   $order->products->filter( function( $product ) use ( $product_id ) {
            return $product->id === $product_id;
        })->flatten();

        return $this->ordersService->refundSingleProduct( $order, $product );
    }

    public function addProductToOrder( $order_id, Request $request )
    {
        $order      =   $this->ordersService->getOrder( $order_id );
        return $this->ordersService->addProducts( $order, $request->input( 'products' ) );
    }

    public function listOrders()
    {
        return $this->view( 'pages.dashboard.orders.list', [
            'title' =>  __( 'Orders' )
        ]);
    }

    /**
     * get order products
     * @param int order id
     * @return array or product
     */
    public function getOrderProducts( $id )
    {
        return $this->ordersService->getOrderProducts( $id );
    }

    public function getOrderPayments( $id )
    {
        return $this->ordersService->getOrderPayments( $id );
    }

    public function deleteOrderProduct( $orderId, $productId )
    {
        $order  =   $this->ordersService->getOrder( $orderId );
        return $this->ordersService->deleteOrderProduct( $order, $productId );
    }

    public function getOrders( Order $id = null ) {
        if ( $id instanceof Order ) {
            // to autoload customer related model
            $id->customer;
            return $id;
        }

        return Order::with( 'customer' )->get();
    }

    public function showPOS()
    {
        return $this->view( 'pages.dashboard.orders.pos', [
            'title'         =>  __( 'Proceeding Order &mdash; NexoPOS' ),
            'orderTypes'    =>  [
                [
                    'identifier'    =>  'takeaway',
                    'label'         =>  'Take Away',
                    'icon'          =>  '/images/groceries.png',
                    'selected'      =>  false
                ], [
                    'identifier'    =>  'delivery',
                    'label'         =>  'Delivery',
                    'icon'          =>  '/images/delivery.png',
                    'selected'      =>  false
                ]
            ],
            'options'           =>  [
                'ns_pos_printing_document'      =>  $this->optionsService->get( 'ns_pos_printing_document', 'receipt' ),
                'ns_pos_printing_enabled_for'   =>  $this->optionsService->get( 'ns_pos_printing_enabled_for', 'only_paid_ordes' ),
            ],
            'urls'              =>  [
                'printing_url'  =>      Hook::filter( 'ns_pos_printing_url', url( '/dashboard/orders/receipt/{id}?dashboard=disabled&autoprint=true' ) )
            ],
            'paymentTypes'  =>  $this->paymentTypes
        ]);
    }

    public function orderInvoice( Order $order )
    {
        $order->load( 'customer' );
        $order->load( 'products' );
        $order->load( 'shipping_address' );
        $order->load( 'billing_address' );
        $order->load( 'user' );

        return $this->view( 'pages.dashboard.orders.templates.invoice', [
            'order'     =>  $order,
            'billing'   =>  ( new CustomerCrud() )->getForm()[ 'tabs' ][ 'billing' ][ 'fields' ],
            'shipping'  =>  ( new CustomerCrud() )->getForm()[ 'tabs' ][ 'shipping' ][ 'fields' ],
            'title'     =>  sprintf( __( 'Order Invoice &mdash; %s' ), $order->code )
        ]);
    }

    public function orderReceipt( Order $order )
    {
        $order->load( 'customer' );
        $order->load( 'products' );
        $order->load( 'shipping_address' );
        $order->load( 'billing_address' );
        $order->load( 'user' );

        return $this->view( 'pages.dashboard.orders.templates.receipt', [
            'order'             =>  $order,
            'title'             =>  sprintf( __( 'Order Receipt &mdash; %s' ), $order->code ),
            'optionsService'    =>  $this->optionsService,
            'ordersService'     =>  $this->ordersService,
            'paymentTypes'      =>  collect( $this->paymentTypes )->mapWithKeys( function( $payment ) {
                return [ $payment[ 'identifier' ] => $payment[ 'label' ] ];
            })
        ]);
    }
}

