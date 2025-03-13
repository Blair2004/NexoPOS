import { OrderType } from "~/interfaces/order-type";
import { Popup } from "~/libraries/popup";
import { StatusResponse } from "~/status-response";
import nsDriversPopup from "~/popups/ns-pos-drivers-popup.vue";

export default {
    identifier: 'handle.delivery-order',
    promise: (selectedType: OrderType) => new Promise<StatusResponse>((resolve, reject) => {
        if ( selectedType && selectedType.identifier === 'delivery') {            
            return Popup.show( nsDriversPopup, { resolve, reject });
        }

        return resolve({
            status: 'success',
            message: 'Proceed'
        });
    })
}