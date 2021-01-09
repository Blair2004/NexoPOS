<template>
    <button @click="openRegisterOptions()" class="flex-shrink-0 h-12 flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
        <i class="mr-1 text-xl las la-cash-register"></i>
        <span>{{ name }}</span>
    </button>
</template>
<script>
import { default as nsPosCashRegistersPopupVue } from '@/popups/ns-pos-cash-registers-popup.vue';
import nsPosCashRegistersOptionsPopupVue from '@/popups/ns-pos-cash-registers-options-popup.vue';
export default {
    data() {
        return {
            order: null,
            name: '',
            selectedRegister: null,
        }
    },
    watch: {
        selectedRegister() {
            if ( POS.get( 'register_id' ) === undefined ) {
                this.name   =   'Cash Register';
            }

            this.name   =   `Cash Register : ${this.selectedRegister.name}`;
        }
    },
    methods: {
        openRegisterOptions() {
            Popup.show( nsPosCashRegistersOptionsPopupVue )
        },
        registerInitialQueue() {
            POS.initialQueue.push( async () => {
                try {
                    const response  =   await new Promise( ( resolve, reject ) => {
                        if ( this.order.register_id === undefined ) {
                            Popup.show( nsPosCashRegistersPopupVue, { resolve, reject });
                        }
                    });

                    /**
                     * we define here the register that will be used
                     * throughout the orders send to the server
                     */
                    this.selectedRegister   =   response.data.register;
                    POS.set( 'register_id', this.selectedRegister.id ); 
                    
                    this.openRegisterOptions();

                    return response;

                } catch( exception ) {
                    throw exception;
                }
            })
        }
    },
    mounted() {
        this.registerInitialQueue();

        POS.order.subscribe( order => {
            this.order  =   order;
        });
    }
}
</script>