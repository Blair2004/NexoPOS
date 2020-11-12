export default class Labels {
    getTypeLabel( label ) {
        switch( label ) {
            // @todo localization needed here
            case 'delivery' : return 'Delivery'; break;
            case 'takeaway' : return 'Take Away'; break;
            default : return 'Unknown Type'; break;
        }
    }
    getDeliveryStatus( label ) {
        switch( label ) {
            // @todo localization needed here
            case 'pending' : return 'Pending'; break;
            case 'ongoing' : return 'Ongoing'; break;
            case 'delivered' : return 'Delivered'; break;
            case 'failed' : return 'Delivery Failure'; break;
            default : return 'Unknown Status'; break;
        }
    }
    getProcessingStatus( label ) {
        switch( label ) {
            // @todo localization needed here
            case 'pending' : return 'Pending'; break;
            case 'ongoing' : return 'Ongoing'; break;
            case 'done' : return 'Done'; break;
            case 'failed' : return 'Failure'; break;
            default : return 'Unknown Status'; break;
        }
    }
    getPaymentStatus( label ) {
        switch( label ) {
            // @todo localization needed here
            case 'paid' : return 'Paid'; break;
            case 'hold' : return 'Hold'; break;
            case 'unpaid' : return 'Unpaid'; break;
            case 'partially_paid' : return 'Partially Paid'; break;
            default : return 'Unknown Status'; break;
        }
    }
}