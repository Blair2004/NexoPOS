@extends( 'layout.dashboard' )
@section( 'layout.dashboard.body' )
    <div>
        @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
        <div id="dashboard-content" class="px-4">
            <div class="page-inner-header mb-4">
                <h3 class="text-2xl text-fontcolor font-bold">{{ __( 'Invoice' ) }} — {{ $order->code }}</h3>
                <p class="text-fontcolor-soft">{{ __( 'Order invoice' ) }}</p>
            </div>
            <div class="my-2 w-full mx-auto">
                <div class="flex justify-between items-center mb-2">
                    <ns-link type="info" href="{{ ns()->url( '/dashboard/orders/invoice/' . $order->id . '?dash-visibility=disabled' ) }}">{{ __( 'Hide Dashboard' ) }}</ns-link>
                    <ns-link type="info" href="{{ ns()->url( '/dashboard/orders/invoice/' . $order->id . '?dash-visibility=disabled&autoprint=true' ) }}">
                        <i class="las la-print"></i> {{ __( 'Print' ) }}
                    </ns-link>
                </div>
                @include( Hook::filter( 'ns-web-invoice-template', 'pages.dashboard.orders.templates._invoice', $order ) )
            </div>
        </div>
    </div>
@endsection
