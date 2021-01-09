<template>
    <div class="shadow-lg w-95vw h-95vh md:w-2/5-screen md:h-4/5-screen bg-white">
        <div class="border-b border-gray-200 p-2 text-gray-700 flex justify-between items-center">
            <h3 class="font-semibold">{{ title }}</h3>
            <div><ns-close-button @click="close()"></ns-close-button></div>
        </div>
        <div class="p-2">
            <div class="mb-2 p-3 bg-green-400 font-bold text-white text-right">
                {{ amount | currency }}
            </div>
            <div class="mb-2">
                <ns-numpad @next="submit()" :value="0" @changed="definedValue( $event )"></ns-numpad>
            </div>
            <ns-field v-for="(field,index) of fields" :field="field" :key="index"></ns-field>
        </div>
    </div>
</template>
<script>
import nsNumpadVue from '@/components/ns-numpad.vue';
export default {
    components: {
        nsNumpad: nsNumpadVue
    },
    data() {
        return {
            amount: 0,
            title: null,
            identifier: null,
            action: null,
            fields: [],
        }
    },
    mounted() {
        this.title          =   this.$popupParams.title;
        this.identifier     =   this.$popupParams.identifier;
        this.action         =   this.$popupParams.action;
        console.log( this.$popupParams );
        this.loadFields();
    },
    methods: {
        close() {
            this.$popup.close();
        },
        loadFields() {
            nsHttpClient.get( `/api/nexopos/v4/fields/${this.identifier}` )
                .subscribe( result => {
                    this.fields     =   result;
                }, ( error ) => {
                    return nsSnackBar.error( error.message, 'OKAY', { duration : false }).subscribe();
                })
        },
        submit() {
            const fields    =   this.validation.extractFields( this.openFields );
            fields.amount   =   this.amount;

            nsHttpClient.post( `/api/nexopos/v4/cash-registers/${this.action}/${this.selectedRegister.id}`, fields )
                .subscribe( result => {
                    this.$popupParams.resolve( result );
                    this.$popup.close();
                    nsSnackBar.success( result.message ).subscribe();
                }, ( error ) => {
                    nsSnackBar.error( error.message ).subscribe();
                })
        },
        setValue( amount ) {
            this.amount     =   amount;
        }
    }
}
</script>