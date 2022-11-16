import nsAlertPopup from '~/popups/ns-alert-popup.vue';
import nsConfirmPopup from '~/popups/ns-pos-confirm-popup.vue';
import nsMediaPopup from '~/pages/dashboard/ns-media.vue';
import nsOrderPreview from '~/popups/ns-orders-preview-popup.vue';
import nsOrdersRefund from '~/popups/ns-orders-refund-popup.vue';
import nsPOSLoadingPopup from '~/popups/ns-pos-loading-popup.vue';
import nsProcurementQuantity from '~/popups/ns-procurement-quantity.vue';
import nsProductPreview from '~/popups/ns-products-preview.vue';
import nsPromptPopup from '~/popups/ns-prompt-popup.vue';
import nsSelectPopup from '~/popups/ns-select-popup.vue';

const popups    =   { 
    nsOrderPreview,
    nsProductPreview,
    nsAlertPopup,
    nsConfirmPopup,
    nsPromptPopup,
    nsMediaPopup,
    nsProcurementQuantity,
    nsOrdersRefund,
    nsSelectPopup,
    nsPOSLoadingPopup,
};

for( let index in popups ) {
    window[ index ]     =   popups[ index ];
}