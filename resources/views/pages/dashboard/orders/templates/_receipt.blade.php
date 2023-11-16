<?php
use App\Models\Order;
use App\Classes\Hook;
use Illuminate\Support\Facades\View;

?>
<div class="w-full h-full">
    <div class="w-full md:w-1/2 lg:w-1/3 shadow-lg bg-white p-2 mx-auto">
        <div class="flex items-center justify-center">
            @if ( empty( ns()->option->get( 'ns_invoice_receipt_logo' ) ) )
            <h3 class="text-3xl font-bold">{{ ns()->option->get( 'ns_store_name' ) }}</h3>
            @else
            <img src="{{ ns()->option->get( 'ns_invoice_receipt_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
            @endif
        </div>
        <div class="p-2 border-b border-gray-700">
            <div class="flex flex-wrap -mx-2 text-sm">
                <div class="px-2 w-1/2">
                    {!! nl2br( $ordersService->orderTemplateMapping( 'ns_invoice_receipt_column_a', $order ) ) !!}
                </div>
                <div class="px-2 w-1/2">
                    {!! nl2br( $ordersService->orderTemplateMapping( 'ns_invoice_receipt_column_b', $order ) ) !!}
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
                    @foreach( Hook::filter( 'ns-receipt-products', $order->combinedProducts ) as $product )
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-700">
                            <?php $productName  =   View::make( 'pages.dashboard.orders.templates._product-name', compact( 'product' ) );?>
                            <?php echo Hook::filter( 'ns-receipt-product-name', $productName->render(), $product );?>
                        </td>
                        <td class="p-2 border-b border-gray-800 text-right">{{ ns()->currency->define( $product->total_price ) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tbody>
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">{{ __( 'Sub Total' ) }}</td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $order->subtotal ) }}</td>
                    </tr>
                    @if ( $order->discount > 0 )
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">
                            <span>{{ __( 'Discount' ) }}</span>
                            @if ( $order->discount_type === 'percentage' )
                            <span>({{ $order->discount_percentage }}%)</span>
                            @endif
                        </td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $order->discount ) }}</td>
                    </tr>
                    @endif
                    @if ( $order->total_coupons > 0 )
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">
                            <span>{{ __( 'Coupons' ) }}</span>
                        </td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $order->total_coupons ) }}</td>
                    </tr>
                    @endif
                    @if ( ns()->option->get( 'ns_invoice_display_tax_breakdown' ) === 'yes' ) 
                        @foreach( $order->taxes as $tax )
                        <tr>
                            <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">
                                <span>{{ $tax->tax_name }} &mdash; {{ $order->tax_type === 'inclusive' ? __( 'Inclusive' ) : __( 'Exclusive' ) }}</span>
                            </td>
                            <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $tax->tax_value ) }}</td>
                        </tr>
                        @endforeach
                        @if ( $order->products_tax_value > 0 )
                        <tr>
                            <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">
                                <span>{{ $order->tax_type === 'inclusive' ? __( 'Inclusive Product Taxes' ) : __( 'Exclusive Product Taxes' ) }}</span>
                            </td>
                            <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $order->products_tax_value ) }}</td>
                        </tr>
                        @endif
                    @else                     
                        @if ( $order->tax_value > 0 )
                        <tr>
                            <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">
                                <span>{{ __( 'Taxes' ) }}</span>
                            </td>
                            <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $order->tax_value ) }}</td>
                        </tr>
                        @elseif ( $order->products_tax_value > 0 )
                        <tr>
                            <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">
                            <span>{{ $order->tax_type === 'inclusive' ? __( 'Inclusive Product Taxes' ) : __( 'Exclusive Product Taxes' ) }}</span>
                            </td>
                            <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $order->products_tax_value ) }}</td>
                        </tr>
                        @endif
                    @endif
                    @if ( $order->shipping > 0 )
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">{{ __( 'Shipping' ) }}</td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $order->shipping ) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">{{ __( 'Total' ) }}</td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $order->total ) }}</td>
                    </tr>
                    @foreach( $order->payments as $payment )
                    <tr>
                        <td class="p-2 border-b border-gray-800 text-sm font-semibold" colspan="2">{{ $paymentTypes[ $payment[ 'identifier' ] ] ?? __( 'Unknown Payment' ) }}</td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $payment[ 'value' ] ) }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">{{ __( 'Paid' ) }}</td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $order->tendered ) }}</td>
                    </tr>
                    @if ( in_array( $order->payment_status, [ 'refunded', 'partially_refunded' ]) )
                        @foreach( $order->refund as $refund )
                        <tr>
                            <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">{{ __( 'Refunded' ) }}</td>
                            <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( - $refund->total ) }}</td>
                        </tr>
                        @endforeach
                    @endif
                    @switch( $order->payment_status )
                        @case( Order::PAYMENT_PAID )
                        <tr>
                            <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">{{ __( 'Change' ) }}</td>
                            <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( $order->change ) }}</td>
                        </tr>
                        @break
                        @case( Order::PAYMENT_PARTIALLY )
                        <tr>
                            <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">{{ __( 'Due' ) }}</td>
                            <td class="p-2 border-b border-gray-800 text-sm text-right">{{ ns()->currency->define( abs( $order->change ) ) }}</td>
                        </tr>
                        @break
                    @endswitch
                </tbody>
            </table>
            @if( $order->note_visibility === 'visible' )
            <div class="pt-6 pb-4 text-center text-gray-800 text-sm">
                <strong>{{ __( 'Note: ' ) }}</strong> {{ $order->note }}
            </div>
            @endif
            <div class="pt-6 pb-4 text-center text-gray-800 text-sm">
                {{ ns()->option->get( 'ns_invoice_receipt_footer' ) }}
            </div>
        </div>
    </div>
</div>
@includeWhen( request()->query( 'autoprint' ) === 'true', '/pages/dashboard/orders/templates/_autoprint' )