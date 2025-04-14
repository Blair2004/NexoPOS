import { OrderType } from "~/interfaces/order-type";
import { Popup } from "~/libraries/popup";
import { StatusResponse } from "~/status-response";
import nsDriversPopup from "~/popups/ns-pos-drivers-popup.vue";

declare const POS: any;

export default {
    identifier: 'handle.delivery-order',
    promise: (selectedType: OrderType) => new Promise<StatusResponse>((resolve, reject) => {
        const options =     POS.options.getValue();
        const order = POS.order.getValue();

        if ( selectedType && selectedType.identifier === 'delivery' && options.ns_drivers_enabled) {            
            return Popup.show( nsDriversPopup, { resolve, reject, order });
        }

        return resolve({
            status: 'success',
            message: 'Proceed',
            data: { 
                driver_id: null
            }
        });
    })
}