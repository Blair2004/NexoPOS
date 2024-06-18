<template>
    <div class="shadow-lg flex flex-col ns-box w-95vw h-95vh md:w-3/5-screen md:h-3/5-screen lg:w-2/5-screen">
        <div class="p-2 border-b ns-box-header items-center flex justify-between">
            <h3 class="text-semibold">{{ __( 'Order Settings' ) }}</h3>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="p-2 flex-auto border-b ns-box-body overflow-y-auto">
            <ns-field :field="field" v-for="(field, index) of fields" :key="index"></ns-field>
        </div>
        <div class="p-2 flex justify-end ns-box-footer">
            <ns-button @click="saveSettings()" type="info">{{ __( 'Save' ) }}</ns-button>
        </div>
    </div>
</template>
<script>
import FormValidation from '~/libraries/form-validation';
export default {
    name: 'ns-pos-order-settings',
    props: [ 'popup' ],
    mounted() {
        nsHttpClient.get( '/api/fields/ns.pos-order-settings' )
            .subscribe( fields => {
                fields.forEach( field => {
                    field.value     =   this.popup.params.order[ field.name ] || '';
                });
                
                this.fields     =   this.validation.createFields( fields );
            }, ( error ) => {

            });

        this.popupCloser();
    },
    data() {
        return {
            fields: [],
            validation: new FormValidation
        }
    },
    methods: {
        __,

        popupCloser, 
        popupResolver,

        closePopup() {
            this.popupResolver( false );
        },

        saveSettings() {
            const fields    =   this.validation.extractFields( this.fields );
            this.popupResolver( fields );
        }
    }
}
</script>