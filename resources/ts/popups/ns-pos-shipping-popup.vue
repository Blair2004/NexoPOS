<template>
    <div class="ns-box w-6/7-screen md:w-4/5-screen lg:w-3/5-screen h-6/7-screen md:h-4/5-screen shadow-lg flex flex-col overflow-hidden">
        <div class="p-2 border-b ns-box-header flex justify-between items-center">
            <h3 class="font-bold text-primary">{{ __( 'Shipping & Billing' ) }}</h3>
            <div class="tools">
                <button @click="closePopup()" class="ns-close-button rounded-full h-8 w-8 border items-center justify-center">
                    <i class="las la-times"></i>
                </button>
            </div>
        </div>
        <div class="flex-auto ns-box-body p-2 overflow-y-auto ns-tab">
            <div id="tabs-container">
                <div class="header flex" style="margin-bottom: -1px;">
                    <div :key="identifier" v-for="( tab , identifier ) of tabs" @click="toggle( identifier )" :class="tab.active ? 'border-b-0 active' : 'inactive'" class="tab rounded-tl rounded-tr border tab  px-3 py-2 text-primary cursor-pointer" style="margin-right: -1px">{{ tab.label }}</div>
                </div>
                <div class="border ns-tab-item">
                    <div class="px-4">
                        <div class="-mx-4 flex flex-wrap">
                            <div :key="index" :class="'p-4 w-full md:w-1/2 lg:w-1/3'" v-for="(field,index) of activeTabFields">
                                <ns-field @blur="formValidation.checkField( field )" @change="formValidation.checkField( field )" :field="field"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-2 flex justify-between border-t ns-box-footer">
            <div></div>
            <div>
                <ns-button @click="submitInformations()" type="info">{{ __( 'Save' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>
<script>
import resolveIfQueued from "~/libraries/popup-resolver";
import FormValidation from '~/libraries/form-validation';
import popupCloser from "~/libraries/popup-closer";

// declare const __, POS, nsHttpClient;

export default {
    name: 'ns-pos-shipping-popup',
    props: [ 'popup' ],
    computed: {
        activeTabFields() {
            if ( this.tabs !== null ) {
                for( let index in this.tabs ) {
                    if ( this.tabs[ index ].active ) {
                        return this.tabs[ index ].fields;
                    }
                }
            }
            return [];
        },
        useBillingInfo() {
            return this.tabs !== null ? this.tabs.billing.fields[0].value : new Object;
        },
        useShippingInfo() {
            return this.tabs !== null ? this.tabs.shipping.fields[0].value : new Object;
        }
    },
    unmounted() {
        this.orderSubscription.unsubscribe();
    },
    mounted() {
        this.orderSubscription  =   POS.order.subscribe( order => this.order = order ); 

        this.popupCloser();

        this.loadForm();
    },
    data() {
        return {
            tabs : null,
            orderSubscription: null,
            order: null,
            formValidation: new FormValidation
        }
    },
    watch: {
        useBillingInfo( value ) {
            if ( value === 1 ) {
                this.tabs.billing.fields.forEach( field => {
                    if ( field.name !== '_use_customer_billing' ) {
                        field.value     =   this.order.customer.billing ? this.order.customer.billing[ field.name ] : field.value;
                    }
                });
            }
        },
        useShippingInfo( value ) {
            if ( value === 1 ) {
                this.tabs.shipping.fields.forEach( field => {
                    if ( field.name !== '_use_customer_shipping' ) {
                        field.value     =   this.order.customer.shipping ? this.order.customer.shipping[ field.name ] : field.value;
                    }
                });
            }
        }
    },
    methods: {
        __,
        popupCloser,
        
        resolveIfQueued,

        submitInformations() {
            const form  =   this.formValidation.extractForm({ tabs : this.tabs });

            /**
             * That should only update
             * the shipping type and shipping (fees)
             */
            for( let index in form.general ) {
                if ([ 'shipping', 'shipping_rate' ].includes( index ) ) {
                    form.general[ index ]   =   parseFloat( form.general[ index ] );
                }
            }
            
            this.order  =   { ...this.order, ...form.general };
            
            /**
             * delete the information as we don't want 
             * to add it to the addresses
             */
            delete form.general;
            delete form.shipping._use_customer_shipping;
            delete form.billing._use_customer_billing;

            this.order.addresses    =   form;
            
            POS.order.next( this.order );
            POS.refreshCart();
            
            this.resolveIfQueued( true );
        },

        closePopup() {
            this.resolveIfQueued( false );
        },

        toggle( identifier ) {
            for( let key in this.tabs ) {
                this.tabs[ key ].active    =   false;
            }
            this.tabs[ identifier ].active     =   true;
        },
        loadForm() {
            nsHttpClient.get( '/api/forms/ns.pos-addresses' )
                .subscribe( ({tabs}) => {
                    /**
                     * let's populate back the fields
                     * with what might have been set previously.
                     */
                    for( let index in tabs ) {
                        if ( index === 'general' ) {
                            tabs[ index ].fields.forEach( field => {
                                field.value     =   this.order[ field.name ] || '';
                            });
                        } else {
                            tabs[ index ].fields.forEach( field => {
                                field.value     =   this.order.addresses[ index ] ? this.order.addresses[ index ][ field.name ] : '';
                            });
                        }
                    }

                    this.tabs   =   this.formValidation.initializeTabs( tabs );
                });
        }
    }
}
</script>