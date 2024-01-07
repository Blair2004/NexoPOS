@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
        @include( 'common.dashboard.title' )
        <ns-procurement-invoice></ns-procurement-invoice>
    </div>
</div>
<script type="text/x-template" id="ns-procurement-invoice">
<div class="p-4 shadow ns-box">
    <div id="printable-container">

        <div class="my-4 flex justify-between">
            <div>
                <img class="w-72" src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
            </div>
            <div class="text-gray-600">
                {{ sprintf( __( 'Date : %s' ), $procurement->updated_at ) }}
            </div>
        </div>
        <div class="flex flex-wrap -mx-3 text-primary">
            <div class="px-3 w-full print:w-1/2 md:w-1/2">
                <h3 class="font-semibold text-xl border-b-2 border-blue-400 py-2 mb-2">Provider</h3>
                <ul>
                    <li class="py-1"><span class="font-bold">{{ __( 'First Name' ) }}: </span> {{ $procurement->provider->first_name }}</li>
                    <li class="py-1"><span class="font-bold">{{ __( 'Last Name' ) }}: </span>{{ $procurement->provider->last_name ?? __( 'N/A' ) }}</li>
                    <li class="py-1"><span class="font-bold">{{ __( 'Email' ) }}: </span>{{ $procurement->provider->email ?? __( 'N/A' ) }}</li>
                    <li class="py-1"><span class="font-bold">{{ __( 'Phone' ) }}: </span>{{ $procurement->provider->phone ?? __( 'N/A' ) }}</li>
                    <li class="py-1"><span class="font-bold">{{ __( 'First Address' ) }}: </span>{{ $procurement->provider->address_1 ?? __( 'N/A' ) }}</li>
                    <li class="py-1"><span class="font-bold">{{ __( 'Second Address' ) }}: </span>{{ $procurement->provider->address_2 ?? __( 'N/A' ) }}</li>
                </ul>
            </div>
            <div class="px-3 w-full print:w-1/2 md:w-1/2">
                <h3 class="font-semibold text-xl border-b-2 border-blue-400 py-2 mb-2">Store</h3>
                <ul>
                    <li class="py-1"><span class="font-bold">{{ __( 'Name' ) }}: </span> {{ $options->get( 'ns_store_name' ) }}</li>
                    <li class="py-1"><span class="font-bold">{{ __( 'Address' ) }}: </span>{{ $options->get( 'ns_store_address' ) ?? __( 'N/A' ) }}</li>
                    <li class="py-1"><span class="font-bold">{{ __( 'City' ) }}: </span>{{ $options->get( 'ns_store_city' ) ?? __( 'N/A' ) }}</li>
                    <li class="py-1"><span class="font-bold">{{ __( 'Phone' ) }}: </span>{{ $options->get( 'ns_store_phone' ) ?? __( 'N/A' ) }}</li>
                    <li class="py-1"><span class="font-bold">{{ __( 'Email' ) }}: </span>{{ $options->get( 'ns_store_email' ) ?? __( 'N/A' ) }}</li>
                    <li class="py-1"><span class="font-bold">{{ __( 'PO.Box' ) }}: </span>{{ $options->get( 'ns_store_pobox' ) ?? __( 'N/A' ) }}</li>
                </ul>
            </div>
        </div>
        <div class="my-4">
            <table class="ns-table">
                <thead class="border">
                    <tr>
                        <th>{{ __( 'Product' ) }}</th>
                        <th>{{ __( 'Unit' ) }}</th>
                        <th>{{ __( 'Price' ) }}</th>
                        <th>{{ __( 'Quantity' ) }}</th>
                        <th>{{ __( 'Total' ) }}</th>
                    </tr>
                </thead>
                <tbody class="border">
                    @foreach( $procurement->products as $product )
                    <tr>
                        <td class="border p-1">
                            <h3 class="font-semibold">{{ $product->name }}</h3>
                            <p class="text-xs">{{ __( 'Barcode' ) }}: {{ $product->barcode }}</p>
                        </td>
                        <td class="border p-1">{{ $product->unit->name }}</td>
                        <td class="text-right border p-1">{{ ( string ) ns()->currency->define( $product->purchase_price ) }}</td>
                        <td class="text-right border p-1">{{ $product->quantity }}</td>
                        <td class="text-right border p-1">{{ ( string ) ns()->currency->define( $product->total_purchase_price ) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="border">
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="border p-1">{{ __( 'Total' ) }}</td>
                        <td class="text-right border p-1">{{ ns()->currency->define( 
                            $procurement->products->map( fn( $product ) => $product->total_purchase_price )->sum()
                        ) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="border p-1">{{ __( 'Delivery Status' ) }}</td>
                        <td class="text-right border p-1">{{ ns()->procurement->getDeliveryLabel( $procurement->delivery_status ) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="border p-1">{{ __( 'Payment Status' ) }}</td>
                        <td class="text-right border p-1">{{ ns()->procurement->getPaymentLabel( $procurement->payment_status ) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="flex justify-between">
        <div></div>
        <div>
            <ns-button @click="printInvoice()" type="info">
                <i class="las la-print"></i>
                {{ __( 'Print' ) }}
            </ns-button>
        </div>
    </div>
</div>
</script>
@endsection