@extends( 'layout.dashboard' )
@section( 'layout.dashboard.body' )
    <div>
        @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
        <div id="dashboard-content" class="px-4">
            <div class="page-inner-header mb-4">
                <h3 class="text-3xl text-primary font-bold">{!! sprintf( __( 'Receipt &mdash; %s' ), $order->code ) !!}</h3>
                <p class="text-secondary">{{ __( 'Order receipt' ) }}</p>
            </div>
            <div class="my-2 w-full mx-auto">                
                <ns-link type="info" href="{{ ns()->url( '/dashboard/orders/receipt/' . $order->id . '?dash-visibility=disabled' ) }}">{{ __( 'Hide Dashboard' ) }}</ns-link>
                @include( Hook::filter( 'ns-web-receipt-template', 'pages.dashboard.orders.templates._receipt' ) )
            </div>
        </div>
    </div>
@endsection