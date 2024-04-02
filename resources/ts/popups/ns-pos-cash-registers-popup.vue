<script>
import { nsHttpClient, nsSnackBar } from '../bootstrap';
import FormValidation from '~/libraries/form-validation';
import nsPosCashRegistersActionPopupVue from './ns-pos-cash-registers-action-popup.vue';
import nsNumpad from "~/components/ns-numpad.vue";
import popupResolver from '~/libraries/popup-resolver';
import { __ } from '~/libraries/lang';

export default {
    name: 'ns-pos-cash-registers-popup',
    props: [ 'popup' ],
    components: {
        nsNumpad
    },
    data() {
        return {
            registers: [],
            priorVerification: false,
            hasLoadedRegisters: false,
            validation: new FormValidation,
            amount: 0,
            settings: null,
            settingsSubscription: null,
        }
    },
    mounted() {
        this.checkUsedRegister();

        this.settingsSubscription   =   POS.settings.subscribe( settings => {
            this.settings    =   settings;
        });
    },
    beforeDestroy() {
        this.settingsSubscription.unsubscribe();
    },
    computed: {
    },
    
    methods: {
        __,

        popupResolver,

        async selectRegister( register ) {
            if ( register.status !== 'closed' ) {
                return nsSnackBar.error( __( 'Unable to open this register. Only closed register can be opened.' ) ).subscribe();
            }

            try {
                const response  =   await new Promise( ( resolve, reject ) => {
                    const title         =   __( 'Open Register : %s' ).replace( '%s', register.name );
                    const action        =   'open';
                    const register_id   =   register.id;
                    const identifier    =   'ns.cash-registers-opening'; // fields identifier
                    Popup.show( nsPosCashRegistersActionPopupVue, { resolve, reject, title, identifier, register, action, register_id })
                });

                this.popupResolver( response );                
            } catch( exception ) {
                this.popup.reject( exception );
            }
        },
        checkUsedRegister() {
            this.priorVerification  =   false;
            nsHttpClient.get( `/api/cash-registers/used` )
                .subscribe({
                    next: result => {
                        this.popup.params.resolve( result );
                        this.popup.close();
                    },
                    error: ( error ) => {
                        this.priorVerification  =   true;
                        nsSnackBar.error( error.message ).subscribe();
                        this.loadRegisters();
                    }
                });
        },
        loadRegisters() {
            this.hasLoadedRegisters     =   false;
            nsHttpClient.get( `/api/cash-registers` )
                .subscribe( result => {
                    this.registers              =   result;
                    this.hasLoadedRegisters     =   true;
                })
        },
        getClass( register ) {
            switch( register.status ) {
                case 'in-use':
                    return 'elevation-surface warning cursor-not-allowed';
                case 'disabled':
                    return 'elevation-surface cursor-not-allowed';
                case 'available':
                    return 'elevation-surface success';
            }
            return 'elevation-surface hoverable cursor-pointer';
        }
    }
}
</script>
<template>
    <div>
        <div v-if="priorVerification === false" class="h-full w-full py-10 flex justify-center items-center">
            <ns-spinner size="24" border="8"></ns-spinner>
        </div>
        <div v-if="priorVerification" 
            id="ns-pos-cash-registers-popup"
            class="w-95vw md:w-3/5-screen lg:w-3/5-screen xl:w-2/5-screen flex flex-col overflow-hidden" :class="priorVerification ? 'shadow-lg ns-box' : ''">
            <div class="title p-2 border-b ns-box-header flex justify-between items-center">
                <h3 class="font-semibold">{{ __( 'Open The Cash Register' ) }}</h3>
                <div v-if="settings">
                    <a :href="settings.urls.orders_url" class="rounded-full border ns-close-button px-3 text-sm py-1">{{ __( 'Exit To Orders' ) }}</a>
                </div>
            </div>                
            <div v-if="! hasLoadedRegisters" class="py-10 flex-auto overflow-y-auto flex items-center justify-center">
                <ns-spinner size="16" border="4"></ns-spinner>
            </div>
            <div class="flex-auto overflow-y-auto" v-if="hasLoadedRegisters">
                <div class="grid grid-cols-3">
                    <div @click="selectRegister( register )" v-for="(register, index) of registers" 
                        :class="getClass( register )"
                        :key="index" class="border flex items-center justify-center flex-col p-3">
                        <i class="las la-cash-register text-6xl"></i>
                        <h3 class="text-semibold text-center">{{ register.name }}</h3>
                        <span class="text-sm">({{ register.status_label }})</span>
                    </div>
                </div>
                <div v-if="registers.length === 0" class="p-2 alert text-white">
                    {{ __( 'Looks like there is no registers. At least one register is required to proceed.' ) }} &mdash; <a class="font-bold hover:underline" :href="settings.urls.registers_url">{{ __( 'Create Cash Register' ) }}</a>
                </div>
            </div>
        </div>
    </div>
</template>