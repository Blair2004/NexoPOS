<template>
    <div>
        <div class="shadow-lg w-95vw md:w-3/5-screen ns-box" v-if="loaded">
            <div class="border-b ns-box-header p-2 text-primary flex justify-between items-center">
                <h3 class="font-semibold">{{ title }}</h3>
                <div><ns-close-button @click="close()"></ns-close-button></div>
            </div>
            <div class="p-2">
                <div>
                    <div v-if="register !== null" class="mb-2 p-3 elevation-surface font-bold border text-right flex justify-between">
                        <span>{{ __( 'Balance' ) }} </span>
                        <span>{{ nsCurrency( register.balance ) }}</span>
                    </div>
                    <div class="mb-2 p-3 elevation-surface success border font-bold text-right flex justify-between">
                        <span>{{ __( 'Input' ) }}</span>
                        <span>{{ nsCurrency( amount ) }}</span>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:-mx-2">
                    <div class="md:px-2 md:w-1/2 w-full">
                        <ns-numpad :floating="true" @next="submit( $event )" :value="amount" @changed="definedValue( $event )"></ns-numpad>
                    </div>
                    <div class="md:px-2 md:w-1/2 w-full">
                        <ns-field v-for="(field,index) of fields" :field="field" :key="index"></ns-field>
                    </div>
                </div>
            </div>
        </div>
        <div class="h-full w-full flex items-center justify-center" v-if="! loaded">
            <ns-spinner></ns-spinner>
        </div>
    </div>
</template>
<script>
import FormValidation from '~/libraries/form-validation';
import popupCloser from '~/libraries/popup-closer';
import nsPosConfirmPopupVue from './ns-pos-confirm-popup.vue';
import { __ } from '~/libraries/lang';
import { nsCurrency } from "~/filters/currency";

export default {
    components: {
        // ...
    },
    props: [ 'popup' ],
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
            isSubmitting: false,
        }
    },
    mounted() {
        this.title                  =   this.popup.params.title;
        this.identifier             =   this.popup.params.identifier;
        this.register               =   this.popup.params.register;
        this.action                 =   this.popup.params.action;
        this.register_id            =   this.popup.params.register_id;
        this.settingsSubscription   =   POS.settings.subscribe( settings => {
            this.settings           =   settings;
        });
        this.loadFields();
        this.popupCloser();
    },
    unmounted() {
        this.settingsSubscription.unsubscribe();
    },
    methods: {
        popupCloser,
        nsCurrency,
        __,

        definedValue( value ) {
            this.amount     =   value;
        },
        close() {
            this.popup.close();
        },
        loadFields() {
            this.loaded     =   false;
            nsHttpClient.get( `/api/fields/${this.identifier}` )
                .subscribe( result => {
                    this.loaded     =   true;
                    this.fields     =   result;
                }, ( error ) => {
                    this.loaded     =   true;
                    return nsSnackBar.error( error.message, __( 'OKAY' ), { duration : false }).subscribe();
                });            
        },
        submit( amount ) {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: this.popup.params.confirmMessage || __( 'Would you like to confirm your action.' ),
                onAction: ( action ) => {
                    if ( action ) {
                        this.triggerSubmit();
                    }
                }
            })
        },
        triggerSubmit() {
            if ( this.isSubmitting ) {
                return;
            }

            if ( this.validation.validateFields( this.fields ) === false ) {
                return nsSnackBar.error( __( 'Please fill all required fields' )).subscribe();
            }

            this.isSubmitting    =   true;

            const fields    =   this.validation.extractFields( this.fields );
            fields.amount   =   this.amount === '' ? 0 : this.amount;

            console.log({ fields })

            nsHttpClient.post( `/api/cash-registers/${this.action}/${this.register_id || this.settings.register.id}`, fields )
                .subscribe({
                    next: result => {
                        this.popup.params.resolve( result );
                        this.popup.close();
                        nsSnackBar.success( result.message ).subscribe();
                        this.isSubmitting    =   false;
                    }, 
                    error: ( error ) => {
                        nsSnackBar.error( error.message ).subscribe();
                        this.isSubmitting    =   false;
                    }
                });
        },
    }
}
</script>