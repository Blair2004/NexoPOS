import { Order } from "../../../../../interfaces/order";
import { Popup } from "../../../../../libraries/popup";
import { Queue } from "../../../../../contracts/queue";
const customerPopup     =   require( '../../popups/ns-pos-customer-popup' ).default;

export class CustomerQueue implements Queue {
    constructor( private order: Order ) {};

    run() {
        return new Promise<any>( ( resolve, reject ) => {
            if ( this.order.customer === undefined ) {
                return Popup.show( customerPopup, { resolve, reject });
            }
            return resolve( true );
        });
    }
}