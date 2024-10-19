@inject( 'ordersService', 'App\Services\OrdersService' )
@extends( 'layout.base' )
@section( 'layout.base.body' )
<div class="w-full h-full">
    <div class="w-full md:w-1/2 lg:w-1/3 shadow-lg bg-white p-2 mx-auto">
        <div class="flex items-center justify-center">
            @if ( empty( ns()->option->get( 'ns_invoice_receipt_logo' ) ) )
            <h3 class="text-3xl font-bold">{{ ns()->option->get( 'ns_store_name' ) }}</h3>
            @else
            <img src="{{ ns()->option->get( 'ns_invoice_receipt_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
            @endif
        </div>
        <div class="table w-full">
            <table class="w-full">
                <thead>
                    <tr class="font-semibold">
                        <td colspan="2" class="p-1">{{ __( 'Report On' ) }}</td>
                        <td class="p-1 text-right">{{ ns()->date->getNowFormatted() }}</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Sales Person' ) }}</td>
                        <td class="p-1 text-right">{{ $user->first_name . ' ' . $user->last_name }} ({{ $user->username }})</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <h2 class="text-center font-bold border-b border-dashed py-2     text-black">{{ __( 'General Details' ) }}</h2>
            <br>
            <table class="w-full">
                <tbody>
                    <tr class="font-semibold">
                        <td colspan="2" class="p-1">{{ __( 'Terminal' ) }}</td>
                        <td class="p-1 text-right">{{ $register->name }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Opened On' ) }}</td>
                        <td class="p-1 text-right">{{ $openedOn }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Closed On' ) }}</td>
                        <td class="p-1 text-right">{{ $closedOn }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Session Duration' ) }}</td>
                        <td class="p-1 text-right">{{ $sessionDuration }}</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <h2 class="text-center font-bold border-b border-dashed py-2     text-black">{{ __( 'Sales Overview' ) }}</h2>
            <br>
            <table class="w-full">
                <tbody>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Opening Balance' ) }}</td>
                        <td class="p-1 text-right">{{ $openingBalance }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Closing Balance' ) }}</td>
                        <td class="p-1 text-right">{{ $closingBalance }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Total Gross Sales' ) }}</td>
                        <td class="p-1 text-right">{{ $totalGrossSales }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Total Discounts' ) }}</td>
                        <td class="p-1 text-right">{{ $totalDiscounts }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Total Shipping' ) }}</td>
                        <td class="p-1 text-right">{{ $totalShippings }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Total Taxes' ) }}</td>
                        <td class="p-1 text-right">{{ $totalTaxes }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Total' ) }}</td>
                        <td class="p-1 text-right">{{ $totalSales }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="w-full">
                <thead>
                    <tr class="font-semibold">
                        <td colspan="3" class="p-2 border-b border-gray-800 text-center">{{ __( 'Categories Wise Sales' ) }}</td>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @foreach( $categories as $category )
                    <tr>
                        <td colspan="2" class="p-2">
                            {{ $category[ 'name' ] }}
                        </td>
                        <td class="p-2 text-right">{{ $category[ 'quantity' ] }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="2" class="p-2">
                            {{ __( 'Total' ) }}
                        </td>
                        <td class="p-2 text-right">{{ collect( $categories )->sum( 'quantity' ) }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="w-full">
                <thead>
                    <tr class="font-semibold">
                        <td colspan="3" class="p-2 border-b border-gray-800 text-center">{{ __( 'Products Overview' ) }}</td>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @foreach( $products as $product )
                    <tr>
                        <td colspan="2" class="p-2">
                            {{ $product[ 'name' ] }} ({{ $product[ 'quantity' ] }})
                        </td>
                        <td class="p-2 text-right">{{ $product[ 'total_price' ] }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="2" class="p-2">
                            {{ __( 'Total' ) }}
                        </td>
                        <td class="p-2 text-right">{{ collect( $categories )->sum( 'total_price' ) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@includeWhen( request()->query( 'autoprint' ) === 'true', '/pages/dashboard/orders/templates/_autoprint' )
@endsection