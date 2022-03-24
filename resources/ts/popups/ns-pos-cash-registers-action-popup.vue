<template>
    <div>
        <div class="shadow-lg w-95vw md:w-2/5-screen ns-box" v-if="loaded">
            <div class="border-b ns-box-header p-2 text-primary flex justify-between items-center">
                <h3 class="font-semibold">{{ title }}</h3>
                <div><ns-close-button @click="close()"></ns-close-button></div>
            </div>
            <div class="p-2">
                <div v-if="register !== null" class="mb-2 p-3 elevation-surface font-bold border text-right flex justify-between">
                    <span>{{ __( 'Balance' ) }} </span>
                    <span>{{ register.balance | currency }}</span>
                </div>
                <div class="mb-2 p-3 bg-success-primary font-bold text-white text-right flex justify-between">
                    <span>{{ __( 'Input' ) }}</span>
                    <span>{{ amount | currency }}</span>
                </div>
                <div class="mb-2">
                    <ns-numpad :floating="true" @next="submit( $event )" :value="amount" @changed="definedValue( $event )"></ns-numpad>
                </div>
                <ns-field v-for="(field,index) of fields" :field="field" :key="index"></ns-field>
            </div>
        </div>
        <div class="h-full w-full flex items-center justify-center" v-if="! loaded">
            <ns-spinner></ns-spinner>
        </div>
    </div>
</template>
<script>
import nsNumpadVue from '@/components/ns-numpad.vue';
import FormValidation from '@/libraries/form-validation';
import popupCloser from '@/libraries/popup-closer';
import nsPosConfirmPopupVue from './ns-pos-confirm-popup.vue';
import { __ } from '@/libraries/lang';

export default {
    components: {
        nsNumpad: nsNumpadVue
    },
    data() {
        return {
            amount: 0,
            title: null,
            identifier: null,
            settingsSubscription: null,
            settings: null,
            action: null,
            register: null,
            loaded: false,
            register_id: null, // conditionnally provider
            validation: new FormValidation,
            fields: [],
        }
    },
    mounted() {
        this.title                  =   this.$popupParams.title;
        this.identifier             =   this.$popupParams.identifier;
        this.register               =   this.$popupParams.register;
        this.action                 =   this.$popupParams.action;
        this.register_id            =   this.$popupParams.register_id;
        this.settingsSubscription   =   POS.settings.subscribe( settings => {
            this.settings           =   settings;
        });
        this.loadFields();
    },
    destroyed() {
        this.settingsSubscription.unsubscribe();
    },
    methods: {
        popupCloser,
        __,

        definedValue( value ) {
            this.amount     =   value;
        },
        close() {
            this.$popup.close();
        },
        loadFields() {
            this.loaded     =   false;
            nsHttpClient.get( `/api/nexopos/v4/fields/${this.identifier}` )
                .subscribe( result => {
                    this.loaded     =   true;
                    this.fields     =   result;
                }, ( error ) => {
                    this.loaded     =   true;
                    return nsSnackBar.error( error.message, 'OKAY', { duration : false }).subscribe();
                })
        },
        submit( amount ) {
            Popup.show( nsPosConfirmPopupVue, {
                title: 'Confirm Your Action',
                message: this.$popupParams.confirmMessage || 'Would you like to confirm your action.',
                onAction: ( action ) => {
                    if ( action ) {
                        this.triggerSubmit();
                    }
                }
            })
        },
        triggerSubmit() {
            const fields    =   this.validation.extractFields( this.fields );
            fields.amount   =   this.amount === '' ? 0 : this.amount;

            nsHttpClient.post( `/api/nexopos/v4/cash-registers/${this.action}/${this.register_id || this.settings.register.id}`, fields )
                .subscribe( result => {
                    this.$popupParams.resolve( result );
                    this.$popup.close();
                    nsSnackBar.success( result.message ).subscribe();
                }, ( error ) => {
                    nsSnackBar.error( error.message ).subscribe();
                });
        },
    }
}
</script>