import { __ } from "./lang";

export default class Labels {
    getTypeLabel( label ) {
        switch( label ) {
            // @todo localization needed here
            case 'delivery' : return __( 'Delivery' ); break;
            case 'takeaway' : return __( 'Take Away' ); break;
            default : return __( 'Unknown Type' ); break;
        }
    }
    getDeliveryStatus( label ) {
        switch( label ) {
            // @todo localization needed here
            case 'pending' : return __( 'Pending' ); break;
            case 'ongoing' : return __( 'Ongoing' ); break;
            case 'delivered' : return __( 'Delivered' ); break;
            case 'failed' : return __( 'Delivery Failure' ); break;
            default : return __( 'Unknown Status' ); break;
        }
    }
    getProcessingStatus( label ) {
        switch( label ) {
            // @todo localization needed here
            case 'pending' : return __( 'Pending' ); break;
            case 'ongoing' : return __( 'Ongoing' ); break;
            case 'ready' : return __( 'Ready' ); break;
            case 'failed' : return __( 'Failure' ); break;
            default : return __( 'Unknown Status' ); break;
        }
    }
    getPaymentStatus( label ) {
        switch( label ) {
            // @todo localization needed here
            case 'paid' : return __( 'Paid' ); break;
            case 'hold' : return __( 'Hold' ); break;
            case 'unpaid' : return __( 'Unpaid' ); break;
            case 'partially_paid' : return __( 'Partially Paid' ); break;
            default : return __( 'Unknown Status' ); break;
        }
    }
}