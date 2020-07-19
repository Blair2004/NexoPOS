@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( '../common/dashboard-header' )
    <div id="dashboard-content" class="px-4">
        <div class="page-inner-header">
            <h3 class="text-2xl font-bold">{{ __( 'Customers' ) }}</h3>
        </div>
        <ns-crud url="{{ url( 'api/nexopos/v4/crud/ns.customers' ) }}" id="crud-table-body"></ns-crud>
    </div>
</div>
@endsection