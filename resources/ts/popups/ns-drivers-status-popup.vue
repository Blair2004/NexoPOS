<template>
    <div class="shadow ns-box  w-[95vw] md:w-[60vw] lg:w-[40vw] xl:w-[30vw]">
        <div class="p-2 flex justify-between items-center ns-box-header border-b">
            <h3>{{  __( 'Driver: {name}' ).replace( '{name}', row.username ) }}</h3>
            <div>
                <ns-close-button @click="popupResolver( false )"></ns-close-button>
            </div>
        </div>
        <div class="ns-box-body">
            <div class="py-4 flex justify-center items-center" v-if="!loaded">
                <ns-spinner></ns-spinner>
            </div>
            <ns-notice v-if="errorMessage" color="error">
                <template #description>{{ errorMessage }}</template>
            </ns-notice>
            <div v-else class="p-2">
                <ns-field v-for="field of fields" :field="field"></ns-field>
            </div>
        </div>
        <div class="border-t ns-box-footer p-2 flex justify-end">
            <ns-button @click="changeStatus()">{{ __( 'Change Status' ) }}</ns-button>
        </div>
    </div>
</template>
<script lang="ts">
import { nsSnackBar } from '~/bootstrap';
import { nsConfirmPopup } from '~/components/components';
import FormValidation from '~/libraries/form-validation';
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';

declare const Popup;

export default {
    name: 'ns-drivers-status-popup',
    props: [ 'popup', 'row', 'component' ],
    methods: {
        __,
        popupCloser,
        popupResolver,
        loadDriverStatusFields() {
            /**
             * The identifier "driver-status-fields" is defined on the App\Fields\DriverStatusFields class.
             */
            nsHttpClient.get( '/api/fields/driver-status-fields' ).subscribe({
                next: fields => {
                    this.loaded =   true;
                    this.fields =   this.validation.createFields( fields );
                },
                error: error => {
                    this.loaded = true;
                    nsSnackBar.error( error.message );
                    this.errorMessage = error.message;
                }
            })
        },
        changeStatus() {
            if ( this.validation.validateFields( this.fields ) ) {
                Popup.show( nsConfirmPopup, {
                    title: __( 'Confirm Your Action' ),
                    message: __( 'Are you sure you want to change the status of the driver?' ),
                    onAction: ( action ) => {
                        nsHttpClient.post( `/api/drivers/${this.row.id}/status`, this.validation.extractFields( this.fields ) )
                            .subscribe({
                                next: response => {
                                    nsSnackBar.success( response.message );
                                    this.popupResolver( true );
                                    this.component.$emit( 'reload' );
                                },
                                error: error => nsSnackBar.error( error.message )
                            })
                    }
                })
            } else {
                nsSnackBar.error( __( 'Make sure to choose a status for the driver.' ) );
            }
        }
    },
    data() {
        return {
            loaded: false,
            validation: new FormValidation,
            fields: [],
            errorMessage: '',
        }
    },
    mounted() {
        console.log( this.row );
        this.popupCloser();
        this.loadDriverStatusFields();
    }
}
</script>