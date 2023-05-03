<template>
    <div class="shadow-xl ns-box w-95vw md:w-3/5-screen lg:w-3/7-screen h-95vh md:h-3/5-screen lg:h-3/7-screen overflow-hidden flex flex-col">
        <div class="p-2 flex justify-between border-b ns-box-header items-center">
            <h3 class="text-semibold">{{ __( 'Products' ) }}</h3>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div class="flex-auto overflow-y-auto relative ns-scrollbar">
            <div class="p-2">
                <ns-field v-for="(field,index) of fields" :key="index" :field="field"></ns-field>
            </div>
            <div v-if="fields.length === 0" class="h-full w-full flex items-center justify-center">
                <ns-spinner></ns-spinner>
            </div>
        </div>
        <div class="p-2 flex justify-between items-center border-t ns-box-body">
            <div></div>
            <div>
                <ns-button @click="addProduct()" type="info">{{ __( 'Add Product' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>
<script>
import popuCloser from "~/libraries/popup-closer";
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import FormValidation from '~/libraries/form-validation';
import { __ } from '~/libraries/lang';
export default {
    props: [ 'popup' ],
    mounted() {
        this.popuCloser();
        this.loadFields();
        this.product    =   this.popup.params.product;
    },
    data() {
        return {
            formValidation: new FormValidation,
            fields: [],
            product: null
        }
    },
    methods: {
        __,
        popuCloser,
        close() {
            this.popup.params.reject( false ); 
            this.popup.close();
        },

        addProduct() {
            this.formValidation.validateFields( this.fields );

            if ( this.formValidation.fieldsValid( this.fields ) ) {
                const fields    =   this.formValidation.extractFields( this.fields );
                const product   =   { ...this.product, ...fields };
                this.popup.params.resolve( product );
                return this.close();
            }

            nsSnackBar.error( __( 'The form is not valid.' ) ).subscribe();
        },

        loadFields() {
            nsHttpClient.get( '/api/fields/ns.refund-product' )
                .subscribe( fields => {
                    this.fields     =   this.formValidation.createFields( fields );
                    this.fields.forEach( field => {
                        field.value     =   this.product[ field.name ] || '';
                    });
                })
        }
    }
}
</script>