<template>
    <div class="ns-box shadow w-[90vw] md:w-[80vw] lg:w-[70vw] xl:w-[60vw] h-[90vh] md:h-[80vh] lg:h-[70vh] xl:h-[60vh] flex flex-col">
        <div class="flex-shrink-0 flex justify-between items-center p-2 border-b ns-box-header">
            <div>
                <h1 class="text-xl font-bold text-fontcolor text-center">{{ __( 'Drivers' ) }}</h1>
            </div>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="ns-box-body flex-auto">
            <div class="p-2">
                <ns-notice>
                    <template #title>{{  __( 'No Available Driver' ) }}</template>
                    <template #description>{{ __( 'There is no available driver. would youb like to assign it to a busy driver ?' ) }}</template>
                </ns-notice>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4"></div>
        </div>
        <div class="ns-box-footer p-2 border-t flex justify-end" v-if="options.ns_orders_force_driver_selection === 'no'">
            <ns-button @click="proceed()" type="info">{{ __( 'Skip' ) }}</ns-button>
        </div>
    </div>
</template>
<script>
/**
 * This vue component is Declarative as it doesn't
 * mutate the value but instead resolves an object.
 */
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';

export default {
    props: [ 'popup' ],
    methods: {
        __,
        popupResolver,
        popupCloser,
        closePopup() {
            if ( this.options.ns_orders_force_driver_selection === 'yes' ) {
                this.popup.params.reject( false );
                this.popup.close();
            } else {
                this.popupResolver({
                    driver_id: null
                });
            }
        },
        proceed() {
            this.popupResolver({
                driver_id: null
            });
        },
        loadAvailableDrivers() {
            nsHttpClient.get( '/api/drivers/available' )
                .subscribe({
                    next: drivers => {
                        this.drivers    =   drivers;
                    }
                })
        }
    },
    data() {
        return {
            drivers: [],
            options: {},
            optionsSubscription: null,
        }
    },
    mounted() {
        this.popupCloser();
        this.loadAvailableDrivers();

        this.optionsSubscription    =   POS.options.subscribe( ( options ) => {
            this.options = options;
        } );
    },
    beforeDestroy() {
        if ( this.optionsSubscription ) {
            this.optionsSubscription.unsubscribe();
        }
    },
}
</script>