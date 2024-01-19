<template>
    <div class="ns-box shadow-lg w-95vw md:w-3/5-screen lg:w-2/5-screen">
        <div class="p-2 flex justify-between items-center border-b ns-box-header">
            <h3 class="text-blog">{{ __( 'Tax & Summary' ) }}</h3>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="p-2 ns-box-body">
            <ns-tabs :active="activeTab" @changeTab="changeActive( $event )">
                <ns-tabs-item padding="0" :label="__( 'Settings' )" identifier="settings" :active="true">
                    <div class="p-2 border-b ns-box-body">
                        <ns-field v-for="(field,index) of group_fields" :field="field" :key="index"></ns-field>
                    </div>
                    <div class="flex justify-end p-2">
                        <ns-button @click="saveTax()" type="info">{{ __( 'Save' ) }}</ns-button>
                    </div>
                </ns-tabs-item>
                <ns-tabs-item padding="0" :label="__( 'Summary' )" identifier="summary" :active="false">
                    <div class="p-2" v-if="order">
                        <div v-for="tax of order.taxes" :key="tax.id" class="mb-2 border shadow p-2 w-full flex justify-between items-center elevation-surface">
                            <span>{{ tax.name }}</span>
                            <span>{{ nsCurrency( tax.tax_value ) }}</span>
                        </div>
                        <div class="p-2 text-center text-primary" v-if="order.taxes.length === 0">{{ __( 'No tax is active' ) }}</div>
                    </div>
                </ns-tabs-item>
                <ns-tabs-item padding="0" :label="__( 'Product Taxes' )" identifier="product_taxes" :active="false">
                    <div class="p-2" v-if="order">
                        <div class="border shadow p-2 w-full flex justify-between items-center elevation-surface">
                            <span>{{ __( 'Product Taxes' ) }}</span>
                            <span>{{ nsCurrency( order.products_tax_value )  }}</span>
                        </div>
                    </div>
                </ns-tabs-item>
            </ns-tabs>
        </div>
    </div>
</template>
<script>
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import FormValidation from '~/libraries/form-validation';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';

export default {
    name: 'ns-pos-tax-popup',
    props: [ 'popup' ],
    data() {
        return {
            validation: new FormValidation,
            tax_group: [],
            order: null,
            orderSubscriber: null,
            optionsSubscriber: null,
            options: {},
            tax_groups: [],
            activeTab: '',
            group_fields: [
                {
                    label: __( 'Select Tax' ),
                    name: 'tax_group_id',
                    description: __( 'Define the tax that apply to the sale.' ),
                    type: 'select',
                    disabled: true,
                    value: '',
                    validation: 'required',
                    options: []
                }, {
                    label: __( 'Type' ),
                    name: 'tax_type',
                    disabled: true,
                    value: '',
                    description: __( 'Define how the tax is computed' ),
                    type: 'select',
                    validation: 'required',
                    options: [{
                        label: __( 'Exclusive' ),
                        value: 'exclusive',
                    }, {
                        label: __( 'Inclusive' ),
                        value: 'inclusive'
                    }]
                }
            ]
        }
    },
    mounted() {
        this.loadGroups();
        this.popupCloser();

        this.activeTab      =   this.popup.params.activeTab || 'settings';

        this.group_fields.forEach( field => {
            field.value     =   this.popup.params[ field.name ] || undefined;
        });

        this.orderSubscriber    =   POS.order.subscribe( order => {
            this.order      =   order;
        });

        this.optionsSubscriber  =   POS.options.subscribe( options => {
            this.options    =   options;

            /**
             * only if the options allow it, the change 
             * on the vat used is allowed.
             */
            if ( [ 'variable_vat', 'products_variable_vat' ].includes( this.options.ns_pos_vat ) ) {
                this.group_fields.forEach( field => field.disabled = false );
            }
        });
    },
    unmounted() {
        this.orderSubscriber.unsubscribe();
        this.optionsSubscriber.unsubscribe();
    },
    methods: {
        __,
        nsCurrency,
        popupCloser,
        popupResolver,

        changeActive( active ) {
            this.activeTab  =   active;
        },

        closePopup() {
            this.popupResolver(false);
        },

        saveTax() {
            if ( ! this.validation.validateFields( this.group_fields ) ) {
                return nsSnackBar.error( __( 'Unable to proceed the form is not valid.' ) ).subscribe();
            }

            const fields    =   this.validation.extractFields( this.group_fields );

            /**
             * When we save tax, we should instruct NexoPOS to 
             * ignore previously cached tax and fetch new taxes. 
             * This will be done by overwriting the tax_groups.
             */
            fields.tax_groups   =   [];
            
            this.popupResolver( fields );
        },  
        loadGroups() {
            nsHttpClient.get( `/api/taxes/groups` )
                .subscribe( groups => {
                    this.groups     =   groups;
                    this.group_fields.forEach( field => {
                        if ( field.name === 'tax_group_id' ) {
                            field.options   =   this.groups.map( group => {
                                return {
                                    label: group.name,
                                    value: group.id
                                }
                            });
                        }
                    })
                })
        }
    }
}
</script>