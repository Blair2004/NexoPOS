import * as baseComponents  from './components/components';

import { createApp, shallowRef } from 'vue';

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

declare const nsState;

const nsPopups      =   createApp({
    data() {
        return {
            popups: [],
            defaultClass: 'absolute top-0 left-0 w-full h-full items-center flex overflow-y-auto justify-center is-popup'
        }
    },
    mounted() {
        nsState.subscribe( state => {
            if ( state.popups !== undefined ) {
                document.body.focus();
                this.popups     =   shallowRef( state.popups );
                this.$forceUpdate();
            }
        });
    },
    methods: {
        closePopup( popup, event ) {
            /**
             * This means we've strictly clicked on the container
             */
            if ( Object.values( event.target.classList ).includes( 'is-popup' ) && 
                (
                    popup.config !== undefined &&
                    [ undefined, true ].includes( popup.config.closeOnOverlayClick )
                ) || (
                    Object.keys( popup.config ).length === 0
                )
            ) {
                if ( popup.params && typeof popup.params.reject === 'function' ) {
                    popup.params.reject( false );
                    if ( typeof popup.close === 'function' ) {
                        popup.close();
                    }
                    event.stopPropagation();
                } else {
                    popup.close();
                }
            }
        },
        preventPropagation( event ) {
            event.stopImmediatePropagation();
        }
    }
});

for( let name in baseComponents ) {
    nsPopups.component( name, baseComponents[ name ] );
}

document.addEventListener( 'DOMContentLoaded', () => {
    nsPopups.mount( '#dashboard-popups' );
    ( window as any ).nsPopups  =   nsPopups;
});