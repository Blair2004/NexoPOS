<template>
    <ns-button @click="resetDefault()" type="warning">{{ __( 'Reset Default' ) }}</ns-button>
</template>
<script lang="ts">
import { nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';

declare const nsHttpClient, Popup, nsConfirmPopup

export default {
    mounted() {
        console.log( this.parent );
    },
    props: [ 'parent' ],
    methods: {
        __,
        resetDefault() {
            Popup.show( nsConfirmPopup, {
                title: __( 'Confirm Your Action' ),
                message: __( 'This will clear all records and accounts. It\'s ideal if you want to start from scratch. Are you sure you want to reset default settings for accounting?' ),
                onAction: ( action ) => {
                    if ( action ) {
                        this.resetDefaultAccounting();
                    }
                }
            })
        },
        resetDefaultAccounting() {
            nsHttpClient.get( '/api/transactions-accounts/reset-defaults' ).subscribe({
                next: result => {
                    nsSnackBar.success( result.message ).subscribe();
                    this.parent.loadSettingsForm();
                },
                error: error => {
                    nsSnackBar.error( error.message ).subscribe();
                }
            })
        }
    }
}
</script>
