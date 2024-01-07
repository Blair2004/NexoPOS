import { nsSnackBar } from "~/bootstrap";
import { Queue } from "~/contracts/queue";
import { Order } from "~/interfaces/order"
import { __ } from "~/libraries/lang";

export class ProductsQueue implements Queue {
    constructor( private order: Order ) {}

    run() {
        return new Promise( ( resolve, reject ) => {
            if ( this.order.products.length === 0 ) {
                nsSnackBar.error( __( 'You need to provide some products before proceeding.' ) ).subscribe();
                return reject( false );
            }

            return resolve( true );
        })
    }
}

(<any>window).ProductsQueue    =   ProductsQueue;