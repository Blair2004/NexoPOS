@inject( 'ordersService', 'App\Services\OrdersService' )

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
                        <td colspan="2" class="p-2 border-b border-gray-800">{{ __( 'Product' ) }}</td>
                        <td class="p-2 border-b border-gray-800 text-right">{{ __( 'Total' ) }}</td>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-700">
                            
                        </td>
                        <td class="p-2 border-b border-gray-800 text-right">1</td>
                    </tr>
                </tbody>
                <tbody>
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-800 text-sm font-semibold">0</td>
                        <td class="p-2 border-b border-gray-800 text-sm text-right">1</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>