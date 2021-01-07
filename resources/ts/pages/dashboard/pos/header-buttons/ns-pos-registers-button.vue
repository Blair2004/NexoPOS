<template>
    <button @click="openRegisterOptions()" class="flex-shrink-0 h-12 flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
        <i class="mr-1 text-xl las la-cash-register"></i>
        <span>Cash Register</span>
    </button>
</template>
<script>
import { default as nsPosCashRegistersPopupVue } from '@/popups/ns-pos-cash-registers-popup.vue';
export default {
    data() {
        return {
            order: null,
        }
    },
    methods: {
        openRegisterOptions() {
            console.log( 'must open cash register' );
        },
        registerInitialQueue() {
            POS.initialQueue.push( () => {
                return new Promise( ( resolve, reject ) => {
                    if ( this.order.register_id === null ) {
                        Popup.show( nsPosCashRegistersPopupVue, { resolve, reject });
                    }
                })
            })
        }
    },
    mounted() {
        this.registerInitialQueue();

        POS.order.subscribe( order => {
            this.order  =   order;
        })
    }
}
</script>