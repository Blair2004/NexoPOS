import { Order } from "~/interfaces/order";
import { Popup } from "~/libraries/popup";
import { Queue } from "~/contracts/queue";
import { default as nsCustomerSelectionPopup }  from '~/popups/ns-pos-customer-select-popup.vue';

export class CustomerQueue implements Queue {
    constructor( private order: Order ) {};

    run() {
        return new Promise<any>( ( resolve, reject ) => {
            if ( this.order.customer === undefined ) {
                return Popup.show( nsCustomerSelectionPopup, { resolve, reject });
            }
            return resolve( true );
        });
    }
}

(<any>window).CustomerQueue    =   CustomerQueue;