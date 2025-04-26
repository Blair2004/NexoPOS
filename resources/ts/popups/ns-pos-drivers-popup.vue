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
        <div class="ns-box-body flex-auto overflow-y-auto">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <div @click="assignDriver( driver )" v-for="( driver, index ) in drivers" :key="index" :class="order?.driver_id === driver.id ? 'active': ''" class="flex flex-col items-center justify-center p-2 ns-numpad-key">
                    <ns-avatar-image 
                    :name="driver.username"
                    :size="24"
                    :url="driver.attribute ? driver.attribute.avatar_link : '/images/user.png'"></ns-avatar-image>
                    <h2 class="text-lg font-bold" v-if="driver.billing?.first_name || driver.billing?.last_name">{{ driver.billing.first_name }} {{ driver.billing.last_name }}</h2>
                    <h2 class="text-lg font-bold" v-else>{{ driver.username }}</h2>
                    <p class="text-sm">{{ driver.status ? mappedStatus[ driver.status ] : __( 'Unknown' ) }}</p>
                </div>
            </div>
        </div>
        <div class="ns-box-footer flex flex-col md:flex-row p-2 border-t flex justify-between">
            <div>
                <ns-switch @change="loadDrivers( $event )" size="72" :field="field" v-for="field of fields"></ns-switch>
            </div>
            <div>
                <ns-button v-if="options.ns_drivers_force_selection === 'no'" @click="proceed()" type="info">{{ __( 'Skip' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>
<script>
/**
 * This vue component is Declarative as it doesn't
 * mutate the value but instead resolves an object.
 */
import { nsNotice } from '~/bootstrap';
import FormValidation from '~/libraries/form-validation';
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';

export default {
    props: [ 'popup', 'order' ],
    methods: {
        __,
        popupResolver,
        popupCloser,
        closePopup() {
            if ( this.options.ns_drivers_force_selection === 'yes' ) {
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
        assignDriver( driver ) {
            this.popupResolver({
                driver_id: driver.id,
                driver_name: (driver.billing?.first_name || driver.billing?.last_name) ? `${driver.billing?.first_name} ${driver.billing?.last_name}`: driver.username,
            });
        },
        loadDrivers( status ) {
            nsHttpClient.get( [undefined, null].includes( status ) ? '/api/drivers' : `/api/drivers/${status}` )
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
            mappedStatus: {
                'available': __( 'Available' ),
                'busy': __( 'Busy' ),
                'offline': __( 'Offline' ),
                'disabled': __( 'Disabled' ),
            },
            fields: [],
            optionsSubscription: null,
        }
    },
    mounted() {
        this.popupCloser();
        this.loadDrivers();

        /**
         * If the order is already delivered (for edited unpaid order), we should 
         * prevent changing the driver.
         */
        if ( this.order.delivery_status === 'delivered' ) {
            nsNotice.error( __( 'Forbidden Action' ), __( 'The order is already delivered, you cannot change the driver.' ) );
            this.popupResolver( false );
        }

        this.optionsSubscription    =   POS.options.subscribe( ( options ) => {
            this.options = options;
        } );

        this.fields     =   ( new FormValidation ).createFields([{
            type: 'switch',
            name: 'status',
            options: [{
                label: __( 'All' ),
                value: null,
            }, {
                label: __( 'Available' ),
                value: 'available',
            }, {
                label: __( 'Busy' ),
                value: 'busy',
            }, {
                label: __( 'Offline' ),
                value: 'offline',
            }],
        }]);
    },
    beforeDestroy() {
        if ( this.optionsSubscription ) {
            this.optionsSubscription.unsubscribe();
        }
    },
}
</script>