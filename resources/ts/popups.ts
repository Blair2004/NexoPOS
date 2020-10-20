import { default as nsOrderPreview } from '@/popups/ns-orders-preview-popup.vue';
import { default as nsProductPreview } from '@/popups/ns-products-preview.vue';
import { default as nsAlertPopup } from '@/popups/ns-alert-popup.vue';
import { default as nsConfirmPopup } from '@/popups/ns-pos-confirm-popup.vue';
import { default as nsPromptPopup } from '@/popups/ns-prompt-popup.vue';

const popups    =   { 
    nsOrderPreview,
    nsProductPreview,
    nsAlertPopup,
    nsConfirmPopup,
    nsPromptPopup,
};

for( let index in popups ) {
    window[ index ]     =   popups[ index ];
}