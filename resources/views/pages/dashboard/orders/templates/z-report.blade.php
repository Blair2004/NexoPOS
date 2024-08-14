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
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Terminal' ) }}</td>
                        <td class="p-1 text-right">{{ $register->name }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Opened On' ) }}</td>
                        <td class="p-1 text-right">{{ ns()->date->getFormatted( $opening->created_at ) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Closed On' ) }}</td>
                        <td class="p-1 text-right">{{ $closing !== null ? ns()->date->getFormatted( $closing->created_at ) : __( 'Still Opened' ) }}</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <table class="w-full">
                <tbody>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Opening Balance' ) }}</td>
                        <td class="p-1 text-right">{{ ns()->currency->define( $opening->value ) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Closing Balance' ) }}</td>
                        <td class="p-1 text-right">{{ $closing !== null ? ns()->currency->define( $opening->value ) : __( 'N/A' ) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Difference' ) }}</td>
                        <td class="p-1 text-right">{{ ns()->currency->define( $difference ) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Gross Sales' ) }}</td>
                        <td class="p-1 text-right">{{ ns()->currency->define( $totalGrossSales ) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Discount Amount' ) }}</td>
                        <td class="p-1 text-right">{{ ns()->currency->define( $totalDiscount ) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="p-1">{{ __( 'Total' ) }}</td>
                        <td class="p-1 text-right">{{ ns()->currency->define( $total ) }}</td>
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
                        <td colspan="3" class="p-2 border-b border-gray-800 text-center">{{ __( 'Price List Details' ) }}</td>
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
        </div>
    </div>
</div>
@endsection