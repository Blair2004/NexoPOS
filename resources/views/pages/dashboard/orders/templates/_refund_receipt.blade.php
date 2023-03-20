<?php
use App\Classes\Hook;
use App\Models\PaymentType;
use App\Services\OrdersService;

$ordersService  =   app()->make( OrdersService::class );
?>
<div class="w-full h-full">
    <div class="w-full md:w-1/2 lg:w-1/3 shadow-lg bg-white p-2 mx-auto">
        <div class="flex items-center justify-center">
            <h3 class="text-3xl font-bold">{{ ns()->option->get( 'ns_store_name' ) }}</h3>
        </div>
        <div class="p-2 border-b border-gray-700">
            <div class="flex flex-wrap -mx-2 text-sm">
                <div class="px-2 w-1/2">
                    {!! nl2br( $ordersService->orderTemplateMapping( 'ns_invoice_receipt_column_a', $refund->order ) ) !!}
                </div>
                <div class="px-2 w-1/2">
                    {!! nl2br( $ordersService->orderTemplateMapping( 'ns_invoice_receipt_column_b', $refund->order ) ) !!}
                </div>
            </div>
        </div>
        <div class="table w-full">
            <table class="w-full">
                <thead>
                    <tr class="font-semibold">
                        <td colspan="2" class="p-2 border-b border-gray-800">{{ __( 'Product' ) }}</td>
                        <td class="p-2 border-b border-gray-800 text-right">{{ __( 'Total' ) }}</td>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php
                    $products   =   Hook::filter( 'ns-refund-receipt-products', $refund->refunded_products );
                    ?>
                    @foreach( $products as $product )
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-700">
                            <span class="">{{ $product->product->name }} (x{{ $product->quantity }})</span>
                            <br>
                            <span class="text-xs text-gray-600">{{ $product->unit->name }}</span> &mdash; <span class="text-xs text-gray-600">{{ __( 'Condition:' ) }} {{ $ordersService->getRefundedOrderProductLabel( $product->condition ) }}
                        </td>
                        <td class="p-2 border-b border-gray-800 text-right">{{ Currency::raw( $product->total_price - $product->tax_value ) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tbody>
                    @if ( $refund->tax_value > 0 )
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">{{ __( 'Sub Total' ) }}</td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( 
                            $products->map( fn( $product ) => Currency::raw( $product->total_price - $product->tax_value ) )->sum()
                        ) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">{{ __( 'Tax' ) }}</td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $refund->tax_value ) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">{{ __( 'Total' ) }}</td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $refund->total ) }}</td>
                    </tr>
                    @if ( $refund->shipping > 0 )
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">{{ __( 'Shipping' ) }}</td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $refund->shipping ) }}</td>
                    </tr>
                    @endif
                    <?php
                    $paymentType    =   PaymentType::where( 'identifier', $refund->payment_method )->first();
                    $paymentName    =   $paymentType instanceof PaymentType ? $paymentType->label : __( 'Unknown Payment' );
                    ?>
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">{{ $paymentName }}</td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $refund->total ) }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="pt-6 pb-4 text-center text-gray-800 text-sm">
                {{ ns()->option->get( 'ns_invoice_receipt_footer' ) }}
            </div>
        </div>
    </div>
</div>
@includeWhen( request()->query( 'autoprint' ) === 'true', '/pages/dashboard/orders/templates/_autoprint' )