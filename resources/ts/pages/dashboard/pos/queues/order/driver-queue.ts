import { Queue } from "~/contracts/queue";
import { Popup } from "~/libraries/popup";
import NsPosDriversPopup from "~/popups/ns-pos-drivers-popup.vue";

declare const POS;

export class DriverQueue implements Queue {
    constructor( private order ) {}

    run() { 
        return new Promise( async ( resolve, reject ) => {
            const options = POS.options.getValue();

            if (
                options.ns_drivers_force_selection === 'yes' && 
                this.order.type && this.order.type.identifier === 'delivery' &&
                [ null, undefined ].includes( this.order.driver )
            ) {
                try {
                    const result: { driver_id: null | number } = await new Promise( ( resolve, reject ) => {
                        Popup.show( NsPosDriversPopup, { resolve, reject, order : this.order })
                    });

                    this.order.driver_id = result.driver_id;
                    POS.order.next( this.order );
                    resolve( true );
                } catch ( exception ) {
                    reject( exception );
                }
            } else {
                resolve( true );
            }
        })
    }
}