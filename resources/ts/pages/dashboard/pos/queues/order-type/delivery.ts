import { OrderType } from "~/interfaces/order-type";
import { Popup } from "~/libraries/popup";
import NsPosShippingPopup from "~/popups/ns-pos-shipping-popup.vue";
import { StatusResponse } from "~/status-response";

export default {
    identifier: 'handle.delivery-order',
    promise: (selectedType: OrderType) => new Promise<StatusResponse>((resolve, reject) => {
        if ( selectedType && selectedType.identifier === 'delivery') {
            return Popup.show(NsPosShippingPopup, { resolve, reject });
        }

        return resolve({
            status: 'success',
            message: 'Proceed'
        });
    })
}