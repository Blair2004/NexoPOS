<template>
    <div class="bg-white shadow-lg w-6/7-screen md:w-5/7-screen lg:w-3/7-screen">
        <div class="p-2 border-b border-gray-200">
            <h5 class="font-semibold">Options</h5>
        </div>
        <div class="p-2 border-b border-gray-200">
            <ns-field class="w-full" :field="field" v-for="(field,index) of fields" :key="index"></ns-field>
        </div>
        <div class="p-2 flex justify-end">
            <ns-button @click="applyChanges()" type="info">Save</ns-button>
        </div>
    </div>
</template>
<script>
import FormValidation from '@/libraries/form-validation';
import { nsSnackBar } from '@/bootstrap';
export default {
    name: 'ns-procurement-product-options',
    data() {
        return {
            validation: new FormValidation,
            fields: [],
            rawFields: [
                {
                    label: 'Expiration Date',
                    name: 'expiration_date',
                    description: 'Define when that specific product should expire.',
                    type: 'datetimepicker',
                }, {
                    label: 'Tax Type',
                    name: 'tax_type',
                    description: 'Adjust how tax is calculated on the item.',
                    type: 'select',
                    options: [
                        {
                            label: 'Inclusive',
                            value: 'inclusive',
                        }, {
                            label: 'Exclusive',
                            value: 'exclusive',
                        }
                    ],
                }
            ]
        }
    },  
    methods: {
        applyChanges() {
            const validation    =   this.validation.validateFields( this.fields );
            
            if ( validation ) {
                const fields    =   this.validation.extractFields( this.fields );

                this.$popupParams.resolve( fields );
                return this.$popup.close();
            }

            return nsSnackBar.error( 'Unable to proceed. The form is not valid.' )
                .subscribe();
        }
    },
    mounted() {
        this.$popup.event.subscribe( action => {
            if ( action.event === 'click-overlay' ) {
                this.$popup.close();
            }
        });

        const fields    =   this.rawFields.map( field => {
            if ( field.name === 'expiration_date' ) {
                field.value    =   this.$popupParams.product.procurement.expiration_date
            }

            if ( field.name === 'tax_type' ) {
                field.value    =   this.$popupParams.product.procurement.tax_type
            }

            return field;
        });

        console.log( fields );

        this.fields     =   this.validation.createFields( fields );
    }
}
</script>