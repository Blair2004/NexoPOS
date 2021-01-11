<template>
    <div class="shadow-lg h-95vh md:h-4/6-screen lg:h-3/6-screen w-95vw md:w-4/6-screen lg:w-3/6-screen bg-white flex flex-col">
        <div class="p-2 border-b flex justify-between items-center">
            <h3 class="font-semibold">Layaway Parameters</h3>
            <div>
                <ns-close-button></ns-close-button>
            </div>
        </div>
        <div class="p-2 flex-auto relative">
            <div v-if="fields.length === 0" class="absolute h-full w-full flex items-center justify-center">
                <ns-spinner></ns-spinner>
            </div>
            <ns-field v-for="( field, index ) of fields" :field="field" :key="index"></ns-field>
        </div>
        <div class="p-2 flex border-t justify-between">
            <div></div>
            <div class="-mx-1 flex">
                <div class="px-1">
                    <ns-button @click="close()" type="danger">Cancel</ns-button>
                </div>
                <div class="px-1">
                    <ns-button @click="updateOrder()" type="info">Save</ns-button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import FormValidation from '@/libraries/form-validation'
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
export default {
    name: 'ns-pos-layaway-popup',
    data() {
        return {
            fields: [],
            formValidation: new FormValidation,
            subscription: null
        }
    },  
    mounted() {
        this.loadFields();
        this.subscription   =   this.$popup.event.subscribe( action => {
            if ([ 'click-overlay', 'press-esc' ].includes( action.event ) ) {
                this.close();
            }
        });
    },
    destroyed() {
        this.subscription.unsubscribe();
    },
    methods: {
        close() {
            this.$popupParams.reject({ status: 'faield', message: 'You must define layaway settings before proceeding.' });
            this.$popup.close();
        },
        updateOrder() {
            this.fields.forEach( field => this.formValidation.validateField( field ) );

            if ( ! this.formValidation.fieldsValid( this.fields ) ) {
                return nsSnackBar.error( 'Unable to procee the form is not valid' ).subscribe();
            }

            const fields                =   this.formValidation.extractFields( this.fields );
            const order                 =   { ...this.$popupParams.order, ...fields };
            const { resolve, reject }   =   this.$popupParams;

            POS.order.next( order ); // refresh order globally

            this.$popup.close();
            
            return resolve( order );
        },
        loadFields() {
            nsHttpClient.get( '/api/nexopos/v4/fields/ns.layaway' )
                .subscribe( fields => {
                    this.fields     =   this.formValidation.createFields( fields );
                })
        }
    }
}
</script>