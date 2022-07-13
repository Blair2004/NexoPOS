import { Queue } from "~/contracts/queue";
import { Popup } from "~/libraries/popup";

import { default as nsPaymentPopup } from '~/popups/ns-pos-payment-popup.vue';

export class PaymentQueue implements Queue {
    constructor( private order ) {}

    run() { 
        return new Promise( ( resolve, reject ) => {
            Popup.show( nsPaymentPopup, { resolve, reject, order : this.order })
        })
    }
}

(<any>window).PaymentQueue    =   PaymentQueue;