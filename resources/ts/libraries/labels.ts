import { __ } from "./lang";
declare const typeLabels;
declare const deliveryStatuses;
declare const processingStatuses;
declare const paymentLabels;

export default class Labels {
    getTypeLabel( label ) {
        const type   =   typeLabels.filter( p => p.value === label );
        return type.length > 0 ? type[0].label : __( 'Unknown Status' );
    }
    getDeliveryStatus( label ) {
        const delivery   =   deliveryStatuses.filter( p => p.value === label );
        return delivery.length > 0 ? delivery[0].label : __( 'Unknown Status' );
    }
    getProcessingStatus( label ) {
        const process   =   processingStatuses.filter( p => p.value === label );
        return process.length > 0 ? process[0].label : __( 'Unknown Status' );
    }
    getPaymentStatus( label ) {
        const paymentType   =   paymentLabels.filter( p => p.value === label );
        return paymentType.length > 0 ? paymentType[0].label : __( 'Unknown Status' );
    }
}