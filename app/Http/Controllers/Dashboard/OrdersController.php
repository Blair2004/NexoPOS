<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use App\Services\OrdersService;

// use Tendoo\Core\Services\Page;

class OrdersController extends DashboardController
{
    /** @var OrdersService */
    private $ordersService;

    public function __construct(
        OrdersService $ordersService
    )
    {
        parent::__construct();

        $this->ordersService     =   $ordersService;
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
        return $this->view( 'pages.dashboard.orders', [
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

    public function showPOS()
    {
        return $this->view( 'pages.dashboard.orders.pos', [
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
            ]
        ]);
    }
}

