import { default as nsOrderPreview } from '@/popups/ns-orders-preview-popup.vue';
import { default as nsProductPreview } from '@/popups/ns-products-preview.vue';

const popups    =   { 
    nsOrderPreview,
    nsProductPreview
};

for( let index in popups ) {
    window[ index ]     =   popups[ index ];
}