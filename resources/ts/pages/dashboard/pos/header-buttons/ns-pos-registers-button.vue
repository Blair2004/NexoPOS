<template>
    <button @click="openRegisterOptions()" class="flex-shrink-0 h-12 flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
        <i class="mr-1 text-xl las la-cash-register"></i>
        <span>{{ name }}</span>
    </button>
</template>
<script>
import { default as nsPosCashRegistersPopupVue } from '@/popups/ns-pos-cash-registers-popup.vue';
import nsPosCashRegistersOptionsPopupVue from '@/popups/ns-pos-cash-registers-options-popup.vue';
import { nsSnackBar } from '@/bootstrap';
export default {
    data() {
        return {
            order: null,
            name: '',
            selectedRegister: null,
            orderSubscriber: null,
            settingsSubscriber: null,
        }
    },
    watch: {
    },
    methods: {
        async openRegisterOptions() {
            try {
                const response  =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsPosCashRegistersOptionsPopupVue, { resolve, reject })
                });

                if ( response.button === 'close_register' ) {
                    delete this.settings.register;
                    POS.settings.next( this.settings );
                    POS.reset();
                } 
            } catch( error ) {
                if ( Object.keys( error ).length > 0 ) {
                    nsSnackBar.error( error.message ).subscribe();
                }
            }
        },
        registerInitialQueue() {
            POS.initialQueue.push( async () => {
                try {
                    const response  =   await new Promise( ( resolve, reject ) => {
                        if ( this.settings.register === undefined ) {
                            Popup.show( nsPosCashRegistersPopupVue, { resolve, reject });
                        }
                    });

                    /**
                     * we define here the register that will be used
                     * throughout the orders send to the server
                     */
                    POS.set( 'register', response.data.register ); 
                    POS.reset();

                    return response;
                } catch( exception ) {
                    throw exception;
                }
            });
        },
        setButtonName() {
            if ( this.settings.register === undefined ) {
                return this.name   =   'Cash Register';
            }

            this.name   =   `Cash Register : ${this.settings.register.name}`;
        }
    },
    destroyed() {
        this.orderSubscriber.unsubscribe();
        this.settingsSubscriber.unsubscribe();
    },
    mounted() {
        this.registerInitialQueue();

        this.orderSubscriber    =   POS.order.subscribe( order => {
            this.order  =   order;
        });

        this.settingsSubscriber     =   POS.settings.subscribe( settings => {
            this.settings   =   settings;

            this.setButtonName();
        });
    }
}
</script>