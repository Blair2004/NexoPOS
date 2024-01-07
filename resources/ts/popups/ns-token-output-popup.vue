<template>
    <div id="alert-popup"  class="w-6/7-screen md:w-4/7-screen lg:w-3/7-screen flex flex-col shadow-lg">
        <div class="flex items-center justify-center flex-col flex-auto p-4">
            <h2 class="text-3xl font-body mb-4">{{ __( 'API Token' ) }}</h2>
            <img id="token-qrcode" class="border-2 w-64 h-64" :src="qrCodeSRC"/>
            <p class="text-xs text-secondary py-2">{{ __( 'This QR code is provided to ease authentication on external applications.' ) }}</p>
            <br>
            <p class="py-4  text-center">{{ __( `The API token has been generated. Make sure to copy this code on a safe place as it will only be displayed once.
                If you loose this token, you\'ll need to revoke it and generate a new one.` ) }}</p>
            <input ref="token" v-model="accessToken" readonly type="text" class="my-2 p-2 w-full border-2 rounded border-input-edge bg-input-background">
        </div>
        <div class="action-buttons flex border-t justify-end items-center p-2">
            <ns-button @click="close()" type="info">{{ __( 'Copy And Close' ) }}</ns-button>
        </div>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';
import QRCode from 'qrcode';

export default {
    name: 'ns-token-output',
    props: [ 'popup' ],
    data() {
        return {
            accessToken: '',
            qrCodeSRC: '',
        }
    },
    mounted() {
        this.accessToken    =   this.popup.params.result.data.token.plainTextToken;
        this.popupCloser();
        setTimeout( () => {
            this.$refs[ 'token' ].select();
            this.$refs[ 'token' ].setSelectionRange(0,99999);
            this.setQRCode();
        }, 100 );
    },
    methods: {
        __,
        popupResolver,
        popupCloser,
        setQRCode() {
            QRCode.toDataURL( this.accessToken )
                .then( result => {
                    this.qrCodeSRC  =   result;
                })
                .catch( err => {
                    console.log( err );
                })
        },
        close() {
            navigator.clipboard.writeText( this.$refs[ 'token' ].value );
            this.popupResolver( true );
        },
    }
}
</script>