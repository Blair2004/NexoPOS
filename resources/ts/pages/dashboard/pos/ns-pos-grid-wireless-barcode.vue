<template>
    <button 
        
        class="outline-hidden border-r">
        <!-- :class="wirelessBarcodeConnected ? 'text-green-500' : 'text-blue-500'" -->
        <!-- :title="__( 'Connect/Disconnect wireless barcode reader.' )"  -->
         <!-- @click="openBarcodeConfiguration()" -->
        <template v-if="httpStatus === 'successful'">
            <div @click="openBarcodeConfiguration()" :class="! wirelessBarcodeConnected ? 'bg-blue-500/10' : 'bg-green-500/10'" class="px-2 h-10 flex items-center justify-center">                                
                <i v-if="wirelessBarcodeConnected" class="las la-barcode text-lg"></i>
                <i v-else="wirelessBarcodeConnected" class="las la-circle-notch animate-spin text-lg"></i>
            </div>
        </template>
        <template v-else-if="httpStatus === 'pending'">
            <div class="px-2 h-10 flex items-center justify-center">                                
                <i class="las la-circle-notch animate-spin text-info-secondary text-lg"></i>
            </div>
        </template>
        <template v-else-if="httpStatus === 'error'">
            <div aria-label="foobar" @click="retryConnexion()" class="px-2 h-10 flex text-red-400 bg-red-100 items-center justify-center ">                                
                <i class="las la-exclamation-triangle"></i>
            </div>
        </template>
        <template v-else-if="httpStatus === 'stopped'">
            <div aria-label="foobar" @click="retryConnexion()" class="px-2 h-10 flex text-blue-400 bg-blue-100 items-center justify-center ">                                
                <i class="las la-stop"></i>
            </div>
        </template>
    </button>
</template>

<script lang="ts">
import { nsConfirmPopup } from '~/components/components';
import NsPosGridWirlessBarcodeConnect from './ns-pos-grid-wirless-barcode-connect.vue';
declare const __;
declare const POS;

export default {
    mounted() {
        this.wirelessStateSubscriber = POS.wirelessBarcodeState.subscribe( ( state ) => {
            this.wirelessBarcodeState = state;
            this.wirelessBarcodeConnected = state.socket_status === 'connected';
        } );
        // We'll try to retrieve existing connection here.

        this.httpStatesSubscriber  = POS.wirelessBarcodeState.property( 'http_status' ).subscribe( status => {
            this.httpStatus = status;
        })
    }, 
    beforeUnmount() {
        this.wirelessStateSubscriber.unsubscribe();
    },
    data() {
        return {
            channel: null,
            hash: null,
            httpStatus: 'pending',
            httpStatesSubscriber: null,
            wirelessBarcodeConnected: false,
            wirelessStateSubscriber: null,
            wirelessBarcodeState: {},
        }
    },
    watch: {
        wirelessBarcodeConnected( newValue ) {
            if ( newValue ) {
                // ...
            }
        }
    },
    methods: {
        __,
        retryConnexion() {
            Popup.show(
                nsConfirmPopup, {
                    title: __( 'Wireless Barcode Disconnected' ),
                    message: __( 'We\'ve failed establishing a connexion. Would you like to try again ?' ),
                    onAction: ( action ) => {
                        if ( action ) {
                            POS.initWirelessBarcodeServer();
                        }
                    }
                }                
            )
        },
        async openBarcodeConfiguration() {
            const result = await new Promise( ( resolve, reject ) => {
                Popup.show( NsPosGridWirlessBarcodeConnect, {
                    resolve, reject
                });
            })
        },
    }
}
</script>