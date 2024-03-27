import { Queue } from "~/contracts/queue";
import { Popup } from "~/libraries/popup";
import orderTypePopup from '~/popups/ns-pos-order-type-popup.vue';

export class TypeQueue implements Queue {
    constructor( private order ) {}

    run() {
        return new Promise( ( resolve, reject ) => {
            if ( this.order.type === undefined ) {
                return Popup.show( orderTypePopup, { resolve, reject });
            }
            resolve( true );
        });
    }
}

(<any>window).TypeQueue    =   TypeQueue;