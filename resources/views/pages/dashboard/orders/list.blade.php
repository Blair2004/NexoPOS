<?php
use App\Services\Helper;
?>

@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-800 font-bold">{{ __( 'Managing Orders' ) }}</h3>
            <p class="text-gray-600">{{ __( 'Manage all registered orders.' ) }}</p>
        </div>
        <ns-crud 
            src="{{ ns()->url( 'api/nexopos/v4/crud/ns.orders' ) }}"
            identifier="ns.orders"
            create-url="{{ ns()->url( 'dashboard/pos' ) }}"
            id="crud-table-body">
        </ns-crud>
    </div>
</div>
@endsection
@section( 'layout.dashboard.footer' )
    @parent
<script>
const processingStatus  =   <?php echo json_encode( Helper::kvToJsOptions([
    'pending'   =>  __( 'Pending' ),
    'failed'    =>  __( 'Failed' ),
    'ongoing'   =>  __( 'Ongoing' ),
    'ready'     =>  __( 'Ready' )
]));?>;

document.addEventListener( 'DOMContentLoaded', () => {
    nsEvent.subject().subscribe( event => {
        if ( 
            event.identifier === 'ns-table-row-action' && 
            event.value.action.namespace === 'ns.order-options' 
        ) {
            Popup.show( nsOrderPreview, { order : event.value.row, component : event.value.component });
        }
    });
});
</script>
@endsection