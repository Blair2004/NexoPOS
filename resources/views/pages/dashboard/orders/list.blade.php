<?php
use App\Services\Helper;
use App\Services\OrdersService;
?>
@inject( 'ordersService', OrdersService::class )
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-800 font-bold">{{ __( 'Managing Orders' ) }}</h3>
            <p class="text-gray-600">{{ __( 'Manage all registered orders.' ) }}</p>
        </div>
        <ns-crud 
            src="{{ ns()->url( 'api/nexopos/v4/crud/ns.orders' ) }}"
            identifier="ns.orders"
            create-url="{{ ns()->url( 'dashboard/pos' ) }}">
        </ns-crud>
    </div>
</div>
@endsection
@section( 'layout.dashboard.footer' )
    @parent
<script>
const processingStatuses  =   <?php echo json_encode( Helper::kvToJsOptions( $ordersService->getProcessStatuses() ));?>;
const deliveryStatuses    =   <?php echo json_encode( Helper::kvToJsOptions( $ordersService->getDeliveryStatuses() ));?>;
const typeLabels          =   <?php echo json_encode( Helper::kvToJsOptions( $ordersService->getTypeLabels() ));?>;
const paymentLabels       =   <?php echo json_encode( Helper::kvToJsOptions( $ordersService->getPaymentLabels() ));?>;
const systemOptions       =   <?php echo json_encode([
    'ns_pos_printing_document'      =>  ns()->option->get( 'ns_pos_printing_document', 'receipt' ),
    'ns_pos_printing_gateway'       =>  ns()->option->get( 'ns_pos_printing_gateway', 'default' ),
    'ns_pos_printing_enabled_for'   =>  ns()->option->get( 'ns_pos_printing_enabled_for', 'all_orders' ),
]);?>

const systemUrls      =  <?php echo json_encode([
    'refund_printing_url'   =>  ns()->url( '/dashboard/orders/refund-receipt/{reference_id}?autoprint=true&dash-visibility=disabled' ),
    'sale_printing_url'     =>  ns()->url( '/dashboard/orders/receipt/{reference_id}?autoprint=true&dash-visibility=disabled' ),
    'payment_printing_url'  =>  ns()->url( '/dashboard/orders/receipt/{reference_id}?autoprint=true&dash-visibility=disabled' ),
    'z_report_printing_url' =>  ns()->url( '/dashboard/cash-registers/z-report/{reference_id}?autoprint=true&dash-visibility=disabled' ),
]);?>

document.addEventListener( 'DOMContentLoaded', () => {
    nsEvent.subject().subscribe( event => {
        if ( 
            event.identifier === 'ns-table-row-action' && 
            event.value.action.namespace === 'ns.order-options' 
        ) {
            Popup.show( nsOrderPreview, { order : event.value.row, component : event.value.component });
        }

        if ( 
            event.identifier === 'ns-table-row-action' && 
            event.value.action.namespace === 'ns.order-refunds' 
        ) {
            Popup.show( nsOrdersRefund, { order : event.value.row, component : event.value.component });
        }
    });
});
</script>
@endsection