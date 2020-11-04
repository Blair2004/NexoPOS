@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
    <div>
        @include( '../common/dashboard-header' )
        <div id="dashboard-content" class="px-4">
            <ns-dashboard-cards></ns-dashboard-cards>
            <div class="-m-4 flex flex-wrap">
                <div class="p-4 w-full flex lg:w-1/2">
                    <ns-orders-chart></ns-orders-chart>
                </div>
                <div class="p-4 w-full flex lg:w-1/2">
                    <ns-orders-summary></ns-orders-summary>
                </div>
                <div class="p-4 w-full flex lg:w-1/2">
                    <ns-best-customers></ns-best-customers>
                </div>
                <div class="p-4 w-full flex lg:w-1/2">
                    <ns-best-cashiers></ns-best-cashiers>
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'layout.dashboard.footer.inject' )
<script src="{{ asset( '/js/dashboard.js' ) }}"></script>
@endsection