@extends( 'layout.dashboard' )
@section( 'layout.dashboard.body' )
    <div>
        @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
        <div id="dashboard-content" class="px-4">
            <div class="page-inner-header mb-4">
                <h3 class="text-3xl text-gray-800 font-bold">{!! sprintf( __( 'Receipt &mdash; %s' ), $refund->order->code ) !!}</h3>
                <p class="text-gray-600">{{ __( 'Refund receipt' ) }}</p>
            </div>
            <div class="my-2 w-full mx-auto">                
                <ns-link type="info" href="{{ ns()->url( '/dashboard/orders/refund-receipt/' . $refund->id . '?dash-visibility=disabled' ) }}">{{ __( 'Hide Dashboard' ) }}</ns-link>
                @include( 'pages.dashboard.orders.templates._refund_receipt' )
            </div>
        </div>
    </div>
@endsection