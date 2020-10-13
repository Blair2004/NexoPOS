@extends( 'layout.base' )
@section( 'layout.base.body' )
<div class="w-full h-full">
    <div class="w-full md:w-1/2 lg:w-1/3 shadow-lg bg-white p-2 mx-auto">
        <div class="flex items-center justify-center">
            <h3 class="text-3xl font-bold">{{ $optionsService->get( 'ns_store_name' ) }}</h3>
        </div>
        <div class="flex flex-wrap -mx-2">
            <div class="px-2 w-1/2">
                a
            </div>
            <div class="px-2 w-1/2">
                b
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
                    @foreach( $order->products as $product )
                    <tr>
                        <td colspan="2" class="p-2 border-b border-gray-700">
                            <span class="">{{ $product->name }}</span>
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
                    @foreach( $order->payments as $payment )
                    <tr>
                        <td colspan="2">{{ $payment }}</td>
                        <td></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection