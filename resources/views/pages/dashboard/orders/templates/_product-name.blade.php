<span class="">{{ $product->name }} (x{{ $product->quantity }})</span>
@if ( ns()->option->get( 'ns_invoice_show_product_unit', 'yes' ) !== 'no' )
<br>
<span class="text-xs text-gray-600">{{ $product->unit->name }}</span>
@endif