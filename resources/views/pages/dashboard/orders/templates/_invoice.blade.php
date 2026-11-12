<?php

use App\Classes\Hook;
use Illuminate\Support\Facades\View;

$prefered_price     =   $order->settings?->where( 'key', 'ns_pos_prefered_price' )->first()?->value;
$pos_vat            =   $order->settings?->where( 'key', 'ns_pos_vat' )->first()?->value;
$invoiceLogo        =   ns()->option->get( 'ns_invoice_logo' ) ?: ns()->option->get( 'ns_store_rectangle_logo' );

/**
 * When "Hide N/A Fields" is enabled, fields without a value are
 * removed. A block entirely made of empty fields (e.g. billing
 * details for a walk-in customer) is then hidden as a whole.
 */
$hideUnavailable    =   ns()->option->get( 'ns_invoice_hide_na_values', 'no' ) === 'yes';
$billingFields      =   collect( $billing )->filter( fn( $field ) => ! $hideUnavailable || ! blank( $order->billing_address->{ $field[ 'name' ] } ?? null ) );
$shippingFields     =   collect( $shipping )->filter( fn( $field ) => ! $hideUnavailable || ! blank( $order->shipping_address->{ $field[ 'name' ] } ?? null ) );

/**
 * Block visibility: Order Details shares the row with the invoice
 * meta panel, while Billing/Shipping form the row below it.
 */
$showOrderDetails   =   ns()->option->get( 'ns_invoice_show_store_details', 'yes' ) !== 'no';
$showBilling        =   ns()->option->get( 'ns_invoice_show_billing_details', 'yes' ) !== 'no' && $billingFields->isNotEmpty();
$showShipping       =   ns()->option->get( 'ns_invoice_show_shipping_details', 'yes' ) !== 'no' && $shippingFields->isNotEmpty();

/**
 * The outstanding balance: 0.00 on fully paid orders, matching
 * the "Balance Due" convention of formal invoices.
 */
$balanceDue         =   max( (float) $order->total - (float) $order->tendered, 0 );

/**
 * Font scaling: the declared width is divided by the scale so the
 * zoomed document keeps the same footprint while fonts grow/shrink.
 */
$fontScale          =   (int) ns()->option->get( 'ns_invoice_font_scale', 100 );

/**
 * When no custom Column A/B content is provided, the invoice header
 * falls back to the store information (name, address, city, email,
 * phone) so the document always carries the business identity.
 */
$hasCustomColumns   =   trim( ( string ) ns()->option->get( 'ns_invoice_column_a' ) ) !== ''
    || trim( ( string ) ns()->option->get( 'ns_invoice_column_b' ) ) !== '';

$storeInformation   =   array_values( array_filter( [
    ns()->option->get( 'ns_store_address' ),
    trim( implode( ', ', array_filter( [ ns()->option->get( 'ns_store_city' ), ns()->option->get( 'ns_store_pobox' ) ] ) ) ),
    ns()->option->get( 'ns_store_email' ),
    ns()->option->get( 'ns_store_phone' ),
] ) );
?>
<div class="w-full h-full">
        <div class="w-full mx-auto shadow-lg bg-white p-4 text-gray-800" style="width: calc(100% * 100 / {{ $fontScale }}); max-width: calc(210mm * 100 / {{ $fontScale }}); zoom: {{ $fontScale }}%;">
        <div class="flex flex-wrap -mx-2 justify-between items-start">
            <div class="px-2 flex flex-wrap items-start gap-4">
                @if ( empty( $invoiceLogo ) )
                <h3 class="text-3xl font-bold">{{ ns()->option->get( 'ns_store_name' ) }}</h3>
                @else
                <img src="{{ $invoiceLogo }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}" style="max-height: 72px; max-width: 260px;">
                @endif
                @if ( ! empty( $invoiceLogo ) || count( $storeInformation ) > 0 )
                <div class="text-sm">
                    @if ( ! empty( $invoiceLogo ) )
                    <p class="font-semibold">{{ ns()->option->get( 'ns_store_name' ) }}</p>
                    @endif
                    @foreach( $storeInformation as $line )
                    <p>{{ $line }}</p>
                    @endforeach
                </div>
                @endif
            </div>
            @if ( $hasCustomColumns )
            <div class="px-2 w-full md:w-1/2 text-sm">
                <div class="flex flex-wrap -mx-2">
                    <div class="px-2 w-full md:w-1/2">
                        {!! nl2br( $ordersService->orderTemplateMapping( 'ns_invoice_column_a', $order ) ) !!}
                    </div>
                    <div class="px-2 w-full md:w-1/2">
                        {!! nl2br( $ordersService->orderTemplateMapping( 'ns_invoice_column_b', $order ) ) !!}
                    </div>
                </div>
            </div>
            @endif
        </div>
        <div class="flex flex-wrap -mx-2 my-4 text-sm">
            @if ( $showOrderDetails )
            <div class="px-2 w-full md:w-1/3">
                <h3 class="font-semibold text-base border-b border-gray-400 py-1 mb-1">{{ __( 'Order Details' ) }}</h3>
                <ul>
                    <li class="flex justify-between py-0.5"><span>{{ __( 'Order Code' ) }}</span><span>{{ $order->code }}</span></li>
                    <li class="flex justify-between py-0.5"><span>{{ __( 'Cashier' ) }}</span><span>{{ trim( $order->user->first_name . ' ' . $order->user->last_name ) }}</span></li>
                    <li class="flex justify-between py-0.5"><span>{{ __( 'Date' ) }}</span><span>{{ ns()->date->getFormatted( $order->created_at ) }}</span></li>
                    <li class="flex justify-between py-0.5"><span>{{ __( 'Customer' ) }}</span><span>{{ trim( $order->customer->first_name . ' ' . $order->customer->last_name ) }}</span></li>
                    <li class="flex justify-between py-0.5"><span>{{ __( 'Type' ) }}</span><span>{{ $ordersService->getTypeLabel( $order->type ) }}</span></li>
                    <li class="flex justify-between py-0.5"><span>{{ __( 'Payment Status' ) }}</span><span>{{ $ordersService->getPaymentLabel( $order->payment_status ) }}</span></li>
                    @if ( $order->type === 'delivery' )
                    <li class="flex justify-between py-0.5">
                        <span>{{ __( 'Delivery Status' ) }}</span>
                        <span>{{ $ordersService->getDeliveryStatuses()[ $order->delivery_status ] ?? strtoupper( $order->delivery_status ) }}</span>
                    </li>
                    @endif
                </ul>
            </div>
            @endif
            <div class="px-2 w-full md:w-2/5 ml-auto">
                <h2 class="font-semibold text-base border-b border-gray-400 py-1 mb-1">{{ __( 'Invoice' ) }}</h2>
                <table class="w-full text-sm">
                    <tbody>
                        <tr>
                            <td class="py-0.5 pr-4">{{ __( 'Invoice Number' ) }}</td>
                            <td class="py-0.5 text-right">{{ $order->code }}</td>
                        </tr>
                        <tr>
                            <td class="py-0.5 pr-4">{{ __( 'Invoice Date' ) }}</td>
                            <td class="py-0.5 text-right">{{ ns()->date->getFormatted( $order->created_at ) }}</td>
                        </tr>
                        <tr>
                            <td class="py-0.5 pr-4 font-semibold">{{ __( 'Invoice Total' ) }}</td>
                            <td class="py-0.5 text-right font-semibold">{{ ns()->currency->define( $order->total ) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @if ( $showBilling || $showShipping )
        <div class="flex flex-wrap -mx-2 my-4 text-sm">
            @if ( $showBilling )
            <div class="px-2 w-full md:w-1/3">
                <h3 class="font-semibold text-base border-b border-gray-400 py-1 mb-1">{{ __( 'Billing Details' ) }}</h3>
                <ul>
                    @foreach( $billingFields as $field )
                    <li class="flex justify-between py-0.5">
                        <span>{{ $field[ 'label' ] }}</span>
                        <span>{{ $order->billing_address->{ $field[ 'name' ] } ?? __( 'N/A' ) }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if ( $showShipping )
            <div class="px-2 w-full md:w-1/3">
                <h3 class="font-semibold text-base border-b border-gray-400 py-1 mb-1">{{ __( 'Shipping Details' ) }}</h3>
                <ul>
                    @foreach( $shippingFields as $field )
                    <li class="flex justify-between py-0.5">
                        <span>{{ $field[ 'label' ] }}</span>
                        <span>{{ $order->shipping_address->{ $field[ 'name' ] } ?? __( 'N/A' ) }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        @endif
        <table class="w-full">
            <thead class="bg-gray-100">
                <tr class="font-semibold">
                    <td class="p-2">{{ __( 'Product' ) }}</td>
                    @if ( ns()->option->get( 'ns_invoice_show_unit_price', 'yes' ) !== 'no' )
                    <td class="p-2 text-right">{{ __( 'Unit Price' ) }}</td>
                    @endif
                    <td class="p-2 text-right">{{ __( 'Quantity' ) }}</td>
                    @if ( ns()->option->get( 'ns_invoice_show_discount', 'yes' ) !== 'no' )
                    <td class="p-2 text-right">{{ __( 'Discount' ) }}</td>
                    @endif
                    @if ( ns()->option->get( 'ns_invoice_show_tax_column', 'yes' ) !== 'no' )
                    <td class="p-2 text-right">{{ __( 'Tax' ) }}</td>
                    @endif
                    <td class="p-2 text-right">{{ __( 'Total Price' ) }}</td>
                </tr>
            </thead>
            <tbody class="text-sm">
                @foreach( Hook::filter( 'ns-receipt-products', $order->combinedProducts ) as $product )
                <tr>
                    <td class="p-2">
                        <?php $productName  =   View::make( 'pages.dashboard.orders.templates._product-name', compact( 'product' ) );?>
                        <?php echo Hook::filter( 'ns-receipt-product-name', $productName->render(), $product );?>
                    </td>
                    @if ( ns()->option->get( 'ns_invoice_show_unit_price', 'yes' ) !== 'no' )
                    <td class="p-2 text-right">{{ ns()->currency->define( $product->unit_price ) }}</td>
                    @endif
                    <td class="p-2 text-right">{{ $product->quantity }}</td>
                    @if ( ns()->option->get( 'ns_invoice_show_discount', 'yes' ) !== 'no' )
                    <td class="p-2 text-right">{{ ns()->currency->define( $product->discount ) }}</td>
                    @endif
                    @if ( ns()->option->get( 'ns_invoice_show_tax_column', 'yes' ) !== 'no' )
                    <td class="p-2 text-right">{{ ns()->currency->define( $product->tax_value ?? 0 ) }}</td>
                    @endif
                    <td class="p-2 text-right">{{ ns()->currency->define( $product->total_price ) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="flex justify-end mt-2">
            <table class="w-full md:w-1/2 text-sm">
                <tbody>
                @if ( $pos_vat === 'products_vat' )
                    @if ( $prefered_price === 'net_prices' )
                    <tr>
                        <td class="py-1 pr-4 font-semibold">{{ __( 'Product Taxes' ) }}</td>
                        <td class="py-1 text-right">{{ ns()->currency->define( $order->products_tax_value ) }}</td>
                    </tr>
                    @else
                    <tr>
                        <td class="py-1 pr-4 font-semibold">{{ __( 'Product Taxes (Included)' ) }}</td>
                        <td class="py-1 text-right">{{ ns()->currency->define( $order->products_tax_value ) }}</td>
                    </tr>
                    @endif
                @endif
                @if ( ns()->option->get( 'ns_invoice_show_subtotal', 'yes' ) === 'yes' )
                <tr>
                    <td class="py-1 pr-4 font-semibold">{{ __( 'Sub Total' ) }}</td>
                    <td class="py-1 text-right">{{ ns()->currency->define( $order->subtotal ) }}</td>
                </tr>
                @endif
                @if ( $order->discount > 0 )
                <tr>
                    <td class="py-1 pr-4 font-semibold">
                        <span>{{ __( 'Discount' ) }}</span>
                        @if ( $order->discount_type === 'percentage' )
                        <span>({{ $order->discount_percentage }}%)</span>
                        @endif
                    </td>
                    <td class="py-1 text-right">{{ ns()->currency->define( $order->discount ) }}</td>
                </tr>
                @endif
                @if ( $order->total_coupons > 0 )
                <tr>
                    <td class="py-1 pr-4 font-semibold">{{ __( 'Coupons' ) }}</td>
                    <td class="py-1 text-right">{{ ns()->currency->define( $order->total_coupons ) }}</td>
                </tr>
                @endif
                @if ( ns()->option->get( 'ns_invoice_display_tax_breakdown' ) === 'yes' )
                    @foreach( $order->taxes as $tax )
                    <tr>
                        <td class="py-1 pr-4 font-semibold">
                            <span>{{ $tax->tax_name }} — {{ $order->tax_type === 'inclusive' ? __( 'Inclusive' ) : __( 'Exclusive' ) }}</span>
                        </td>
                        <td class="py-1 text-right">{{ ns()->currency->define( $tax->tax_value ) }}</td>
                    </tr>
                    @endforeach
                    @if ( $order->products_tax_value > 0 )
                    <tr>
                        <td class="py-1 pr-4 font-semibold">
                            <span>{{ $order->tax_type === 'inclusive' ? __( 'Inclusive Product Taxes' ) : __( 'Exclusive Product Taxes' ) }}</span>
                        </td>
                        <td class="py-1 text-right">{{ ns()->currency->define( $order->products_tax_value ) }}</td>
                    </tr>
                    @endif
                @else
                    @if ( $order->tax_value > 0 )
                    <tr>
                        <td class="py-1 pr-4 font-semibold">
                            <span>{{ $order->tax_group?->name ?? __( 'Unassigned Tax Group' ) }} ({{ $order->tax_type === 'inclusive' ? __( 'Inclusive' ) : '' }})</span>
                        </td>
                        <td class="py-1 text-right">{{ ns()->currency->define( $order->tax_value ) }}</td>
                    </tr>
                    @endif
                @endif
                @if ( $order->shipping > 0 )
                <tr>
                    <td class="py-1 pr-4 font-semibold">{{ __( 'Shipping' ) }}</td>
                    <td class="py-1 text-right">{{ ns()->currency->define( $order->shipping ) }}</td>
                </tr>
                @endif
                <tr class="font-bold border-t border-gray-400">
                    <td class="py-1 pr-4">{{ __( 'Total' ) }}</td>
                    <td class="py-1 text-right">{{ ns()->currency->define( $order->total ) }}</td>
                </tr>
                @if ( ns()->option->get( 'ns_invoice_show_payment_rows', 'yes' ) === 'yes' )
                @foreach( $order->payments as $payment )
                <tr>
                    <td class="py-1 pr-4 font-semibold">{{ $paymentTypes[ $payment[ 'identifier' ] ] ?? __( 'Unknown Payment' ) }}</td>
                    <td class="py-1 text-right">{{ ns()->currency->define( $payment[ 'value' ] ) }}</td>
                </tr>
                @endforeach
                @endif
                <tr>
                    <td class="py-1 pr-4 font-semibold">{{ __( 'Paid' ) }}</td>
                    <td class="py-1 text-right">{{ ns()->currency->define( $order->tendered ) }}</td>
                </tr>
                @if ( in_array( $order->payment_status, [ 'refunded', 'partially_refunded' ] ) )
                    @foreach( $order->refunds as $refund )
                    <tr>
                        <td class="py-1 pr-4 font-semibold">{{ __( 'Refunded' ) }}</td>
                        <td class="py-1 text-right">{{ ns()->currency->define( - $refund->total ) }}</td>
                    </tr>
                    @endforeach
                @endif
                @if ( ns()->option->get( 'ns_invoice_show_change_due', 'yes' ) !== 'no' )
                <tr class="bg-gray-100 font-semibold">
                    <td class="py-1 pl-1 pr-4">{{ __( 'Balance Due' ) }}</td>
                    <td class="py-1 pr-1 text-right">{{ ns()->currency->define( $balanceDue ) }}</td>
                </tr>
                @endif
                </tbody>
            </table>
        </div>
        @if( $order->note_visibility === 'visible' )
        <div class="pt-6 pb-4 text-center text-sm">
            <strong>{{ __( 'Note: ' ) }}</strong> {{ $order->note }}
        </div>
        @endif
        <div class="pt-6 pb-4 text-center text-sm">
            {{ ns()->option->get( 'ns_invoice_footer' ) }}
        </div>
    </div>
</div>
@includeWhen( request()->query( 'autoprint' ) === 'true', '/pages/dashboard/orders/templates/_autoprint' )
