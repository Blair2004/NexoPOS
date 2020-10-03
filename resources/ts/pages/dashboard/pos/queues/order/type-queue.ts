import { Queue } from "@/contracts/queue";
import { Popup } from "@/libraries/popup";
import { resolve } from "path";

const orderTypePopup    =   require( '@/pages/dashboard/pos/popups/ns-pos-order-type-popup' ).default;

export class TypeQueue implements Queue {
    constructor( private order ) {}

    run() {
        return new Promise( ( resolve, reject ) => {
            console.log( this.order.type );
            if ( this.order.type === undefined ) {
                return Popup.show( orderTypePopup, { resolve, reject });
            }
            resolve( true );
        });
    }
}