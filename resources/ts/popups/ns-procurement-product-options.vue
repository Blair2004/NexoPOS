<template>
    <div class="ns-box shadow-lg w-6/7-screen md:w-5/7-screen lg:w-3/7-screen">
        <div class="p-2 border-b ns-box-header">
            <h5 class="font-semibold">{{ __( 'Options' ) }}</h5>
        </div>
        <div class="p-2 border-b ns-box-body">
            <ns-field class="w-full" :field="field" v-for="(field,index) of fields" :key="index"></ns-field>
        </div>
        <div class="p-2 flex justify-end ns-box-body">
            <ns-button @click="applyChanges()" type="info">{{ __( 'Save' ) }}</ns-button>
        </div>
    </div>
</template>
<script>
import FormValidation from '~/libraries/form-validation';
import { nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
export default {
    name: 'ns-procurement-product-options',
    props: [ 'popup' ],
    data() {
        return {
            validation: new FormValidation,
            fields: [],
            rawFields: [
                {
                    label: __( 'Expiration Date' ),
                    name: 'expiration_date',
                    description: __( 'Define when that specific product should expire.' ),
                    type: 'datetimepicker',
                }, {
                    label: __( 'Barcode' ),
                    name: 'barcode',
                    description: __( 'Renders the automatically generated barcode.' ),
                    type: 'text',
                    disabled: true,
                }, {
                    label: __( 'Tax Type' ),
                    name: 'tax_type',
                    description: __( 'Adjust how tax is calculated on the item.' ),
                    type: 'select',
                    options: [
                        {
                            label: __( 'Inclusive' ),
                            value: 'inclusive',
                        }, {
                            label: __( 'Exclusive' ),
                            value: 'exclusive',
                        }
                    ],
                }
            ]
        }
    },  
    methods: {
        __,
        applyChanges() {
            const validation    =   this.validation.validateFields( this.fields );
            
            if ( validation ) {
                const fields    =   this.validation.extractFields( this.fields );

                this.popup.params.resolve( fields );
                return this.popup.close();
            }

            return nsSnackBar.error( __( 'Unable to proceed. The form is not valid.' ) )
                .subscribe();
        }
    },
    mounted() {
        const fields    =   this.rawFields.map( field => {
            if ( field.name === 'expiration_date' ) {
                field.value    =   this.popup.params.product.procurement.expiration_date
            }

            if ( field.name === 'tax_type' ) {
                field.value    =   this.popup.params.product.procurement.tax_type
            }

            if ( field.name === 'barcode' ) {
                field.value    =   this.popup.params.product.procurement.barcode
            }

            return field;
        });

        this.fields     =   this.validation.createFields( fields );
    }
}
</script>