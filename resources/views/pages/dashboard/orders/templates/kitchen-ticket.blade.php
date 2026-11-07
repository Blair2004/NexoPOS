<?php
/**
 * Kitchen ticket — printed on the kitchen / bar printer from the
 * browser (window.print). Keep it narrow (80mm thermal) and free of
 * prices: the kitchen only needs what to make, for whom, and when.
 */
?>
@extends( 'layout.dashboard-blank' )

@section( 'layout.dashboard.body' )
<div class="w-full h-full">
    <div class="w-full md:w-1/2 lg:w-1/3 shadow-lg bg-white p-2 mx-auto">
        <div class="text-center border-b border-gray-700 pb-2">
            <h3 class="text-2xl font-bold">{{ ns()->option->get( 'ns_store_name' ) }}</h3>
            <p class="text-lg font-semibold">{{ __( 'Kitchen Ticket' ) }}</p>
            @if ( $zone )
                <p class="text-sm font-semibold">{{ __( 'Zone' ) }}: {{ $zone[ 'name' ] }}</p>
            @endif
        </div>
        <div class="p-2 border-b border-gray-700 text-sm">
            <div class="flex justify-between">
                <span class="font-semibold">{{ __( 'Order' ) }}</span>
                <span class="font-bold">#{{ $order->code }}</span>
            </div>
            <div class="flex justify-between">
                <span>{{ __( 'Time' ) }}</span>
                <span>{{ $order->created_at }}</span>
            </div>
            <div class="flex justify-between">
                <span>{{ __( 'Type' ) }}</span>
                <span>{{ $order->type }}</span>
            </div>
            @if ( $order->customer )
                <div class="flex justify-between">
                    <span>{{ __( 'Customer' ) }}</span>
                    <span>{{ $order->customer->name }}</span>
                </div>
            @endif
            @if ( $order->note )
                <div class="pt-1">
                    <span class="font-semibold">{{ __( 'Note' ) }}:</span>
                    <span>{{ $order->note }}</span>
                </div>
            @endif
        </div>
        <div class="p-2">
            @if ( $items->isEmpty() )
                <p class="text-center text-sm">{{ __( 'All dishes are done' ) }}</p>
            @else
                <table class="w-full">
                    <tbody class="text-base">
                        @foreach( $items as $item )
                            <tr>
                                <td class="p-2 border-b border-gray-700 font-bold w-16">{{ (float) $item->quantity }}{{ $item->unit_name ? ' ' . $item->unit_name : '' }}</td>
                                <td class="p-2 border-b border-gray-700">
                                    {{ $item->name }}
                                    @if ( $item->kitchen_status === 'done' )
                                        <span class="text-sm">({{ __( 'Done' ) }})</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="p-2 text-center text-xs text-gray-600">
            {{ sprintf( __( 'Kitchen Ticket — %s' ), $order->code ) }}
        </div>
    </div>
</div>
@includeWhen( request()->query( 'autoprint' ) === 'true', '/pages/dashboard/orders/templates/_autoprint' )
@endsection
