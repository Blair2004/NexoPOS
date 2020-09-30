import { Queue } from "../../../../../contracts/queue";
import { Popup } from "../../../../../libraries/popup";

const paymentPopup      =   require( '../../popups/ns-pos-payment-popup' ).default;

export class PaymentQueue implements Queue {
    constructor( private order ) {}

    run() { 
        return new Promise( ( resolve, reject ) => {
            Popup.show( paymentPopup, { resolve, reject })
        })
    }
}