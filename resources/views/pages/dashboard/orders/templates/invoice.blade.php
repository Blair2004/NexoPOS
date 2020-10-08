<?php

use App\Models\Order;

?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( '../common/dashboard-header' )
    <div id="dashboard-content" class="px-4">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-800 font-bold">{!! sprintf( __( 'Invoice &mdash; %s' ), $order->code ) !!}</h3>
            <p class="text-gray-600">{{ __( 'Order invoice' ) }}</p>
        </div>
        <div class="my-2">
            <div class="shadow bg-white" id="order-invoice">
                <div class="head p-2 bg-gray-100 flex justify-between border-b border-gray-300">
                    <div class="-mx-2 flex flex-wrap">
                        <div class="px-2">
                            <ns-button type="info">
                                <i class="las la-print"></i> 
                                <span>Print</span>
                            </ns-button>
                        </div>
                        <div class="px-2">
                            <ns-button @click="printPDF()" type="info">
                                <i class="las la-print"></i> 
                                <span>PDF</span>
                            </ns-button>
                        </div>
                    </div>
                </div>
                <div class="body flex flex-col px-2">
                    <div id="invoice-header" class="flex -mx-2 flex-wrap">
                        <div class="w-full md:w-1/3 px-2">
                            <div class="p-2">
                                <h3 class="font-semibold text-xl text-gray-700 border-b border-blue-400 py-2">Store Details</h3>
                                <div class="details">
                                    <ul class="my-1">
                                        <li class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span class="font-semibold">{{ __( 'Order Code' ) }}</span>
                                            <span>{{ $order->code }}</span>
                                        </li>
                                        <li class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span class="font-semibold">{{ __( 'Cashier' ) }}</span>
                                            <span>{{ $order->user->username }}</span>
                                        </li>
                                        <li class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span class="font-semibold">{{ __( 'Date' ) }}</span>
                                            <span>{{ ns()->date->getFormatted( $order->created_at ) }}</span>
                                        </li>
                                        <li class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span class="font-semibold">{{ __( 'Customer' ) }}</span>
                                            <span>{{ $order->customer->name }}</span>
                                        </li>
                                        <li class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span class="font-semibold">{{ __( 'Type' ) }}</span>
                                            <span>{{ ns()->order->getTypeLabel( $order->type ) }}</span>
                                        </li>
                                        <li class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span class="font-semibold">{{ __( 'Payment Status' ) }}</span>
                                            <span>{{ ns()->order->getPaymentLabel( $order->payment_status ) }}</span>
                                        </li>
                                        @if( $order->type === Order::TYPE_DELIVERY )
                                        <li class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span class="font-semibold">{{ __( 'Delivery Status' ) }}</span>
                                            <span>{{ ns()->order->getShippingLabel( $order->delivery_status ) }}</span>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/3 px-2">
                            <div class="p-2">
                                <h3 class="font-semibold text-xl text-gray-700 border-b border-blue-400 py-2">Billing Details</h3>
                                <div class="details">
                                    <ul class="my-1">
                                        @foreach( $billing as $bill )
                                        <li class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span class="font-semibold">{{ $bill[ 'label' ] }}</span>
                                            <span>{{ $order->billing_address->{ $bill[ 'name' ] } ?? 'N/A' }}</span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/3 px-2">
                            <div class="p-2">
                                <h3 class="font-semibold text-xl text-gray-700 border-b border-blue-400 py-2">Shipping Details</h3>
                                <div class="details">
                                    <ul class="my-1">
                                        @foreach( $shipping as $bill )
                                        <li class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span class="font-semibold">{{ $bill[ 'label' ] }}</span>
                                            <span>{{ $order->shipping_address->{ $bill[ 'name' ] } ?? 'N/A' }}</span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table w-full my-4">
                        <table class="table w-full">
                            <thead class="text-gray-600 bg-gray-100">
                                <tr>
                                    <th width="400" class="p-2 border border-gray-200">{{ __( 'Product' ) }}</th>
                                    <th width="200" class="p-2 border border-gray-200">{{ __( 'Unit Price' ) }}</th>
                                    <th width="200" class="p-2 border border-gray-200">{{ __( 'Quantity' ) }}</th>
                                    <th width="200" class="p-2 border border-gray-200">{{ __( 'Discount' ) }}</th>
                                    <th width="200" class="p-2 border border-gray-200">{{ __( 'Tax' ) }}</th>
                                    <th width="200" class="p-2 border border-gray-200">{{ __( 'Total Price' ) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach( $order->products as $product )
                                <tr>
                                    <td class="p-2 border border-gray-200">
                                        <h3 class="text-gray-700">{{ $product->name }}</h3>
                                        <span class="text-sm text-gray-600">{{ $product->unit()->first()->name }}</span>
                                    </td>
                                    <td class="p-2 border border-gray-200 text-center text-gray-700">{{ ns()->currency->define( $product->sale_price ) }}</td>
                                    <td class="p-2 border border-gray-200 text-center text-gray-700">{{ $product->quantity }}</td>
                                    <td class="p-2 border border-gray-200 text-center text-gray-700">{{ ns()->currency->define( $product->discount ) }}</td>
                                    <td class="p-2 border border-gray-200 text-center text-gray-700">{{ ns()->currency->define( $product->tax_value ) }}</td>
                                    <td class="p-2 border border-gray-200 text-right text-gray-700">{{ ns()->currency->define( $product->total_price ) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-100 font-semibold">
                                <tr>
                                    <td class="p-2 border border-gray-200 text-center text-gray-700" colspan="4"></td>
                                    <td class="p-2 border border-gray-200 text-gray-700 text-left">{{ __( 'Sub Total' ) }}</td>
                                    <td class="p-2 border border-gray-200 text-right text-gray-700">{{ ns()->currency->define( $order->subtotal ) }}</td>
                                </tr>
                                <tr>
                                    <td class="p-2 border border-gray-200 text-center text-gray-700" colspan="4"></td>
                                    <td class="p-2 border border-gray-200 text-gray-700 text-left">{{ __( 'Discount' ) }}</td>
                                    <td class="p-2 border border-gray-200 text-right text-gray-700">{{ ns()->currency->define( $order->discount ) }}</td>
                                </tr>
                                <tr>
                                    <td class="p-2 border border-gray-200 text-center text-gray-700" colspan="4"></td>
                                    <td class="p-2 border border-gray-200 text-gray-700 text-left">{{ __( 'Shipping' ) }}</td>
                                    <td class="p-2 border border-gray-200 text-right text-gray-700">{{ ns()->currency->define( $order->shipping ) }}</td>
                                </tr>
                                <tr>
                                    <td class="p-2 border border-gray-200 text-center text-gray-700" colspan="4"></td>
                                    <td class="p-2 border border-gray-200 text-gray-700 text-left">{{ __( 'Total' ) }}</td>
                                    <td class="p-2 border border-gray-200 text-right text-gray-700">{{ ns()->currency->define( $order->total ) }}</td>
                                </tr>
                                <tr>
                                    <td class="p-2 border border-gray-200 text-center text-gray-700" colspan="4"></td>
                                    <td class="p-2 border border-gray-200 text-gray-700 text-left">{{ __( 'Paid' ) }}</td>
                                    <td class="p-2 border border-gray-200 text-right text-gray-700">{{ ns()->currency->define( $order->tendered ) }}</td>
                                </tr>
                                <tr>
                                    <td class="p-2 border border-gray-200 text-center text-gray-700" colspan="4"></td>
                                    <td class="p-2 border border-gray-200 text-gray-700 text-left">{{ __( 'Change' ) }}</td>
                                    <td class="p-2 border border-gray-200 text-right text-gray-700">{{ ns()->currency->define( $order->change ) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
