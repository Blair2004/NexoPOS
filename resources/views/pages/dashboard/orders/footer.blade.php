<?php

use App\Services\Helper;
use App\Services\OrdersService;

$ordersService  =   app()->make( OrdersService::class );
?>
<script>
const processingStatuses  =   <?php echo json_encode( Helper::kvToJsOptions( $ordersService->getProcessStatuses() ));?>;
const deliveryStatuses    =   <?php echo json_encode( Helper::kvToJsOptions( $ordersService->getDeliveryStatuses() ));?>;
const typeLabels          =   <?php echo json_encode( Helper::kvToJsOptions( $ordersService->getTypeLabels() ));?>;
const paymentLabels       =   <?php echo json_encode( Helper::kvToJsOptions( $ordersService->getPaymentLabels() ));?>;
const paymentTypes        =   <?php echo json_encode( Helper::kvToJsOptions( $ordersService->getPaymentTypes() ) );?>;
const systemOptions       =   <?php echo json_encode([
    'ns_pos_printing_document'      =>  ns()->option->get( 'ns_pos_printing_document', 'receipt' ),
    'ns_pos_printing_gateway'       =>  ns()->option->get( 'ns_pos_printing_gateway', 'default' ),
]);?>

const systemSettings        =  <?php echo json_encode([
    'refund_printing_url'   =>  ns()->url( '/dashboard/orders/refund-receipt/{reference_id}?autoprint=true&dash-visibility=disabled' ),
    'sale_printing_url'     =>  ns()->url( '/dashboard/orders/receipt/{reference_id}?autoprint=true&dash-visibility=disabled' ),
    'payment_printing_url'  =>  ns()->url( '/dashboard/orders/payment-receipt/{reference_id}?autoprint=true&dash-visibility=disabled' ),
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