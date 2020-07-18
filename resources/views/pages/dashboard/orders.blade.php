@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( '../common/dashboard-header' )
    <div id="dashboard-content" class="px-4">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-800 font-bold">{{ __( 'Managing Orders' ) }}</h3>
            <p class="text-gray-600">{{ __( 'Manage all registered orders.' ) }}</p>
        </div>
        <ns-crud 
            url="{{ url( 'api/nexopos/v4/crud/ns.orders' ) }}" 
            create-link="{{ url( 'dashboard/pos' ) }}"
            id="crud-table-body">
        </ns-crud>
    </div>
</div>
@endsection