<template>
    <div class="shadow-lg ns-box w-95vw md:w-3/5-screen lg:w-2/5-screen">
        <div class="p-2 flex justify-between items-center border-b ns-box-header">
            <h3 class="font-bold">{{ __( 'Order Note' ) }}</h3>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="p-2">
            <ns-field v-for="(field, index) of fields" :key="index" :field="field"></ns-field>
        </div>
        <div class="p-2 flex justify-end border-t ns-box-footer">
            <ns-button type="info" @click="saveNote()">{{ __( 'Save' ) }}</ns-button>
        </div>
    </div>
</template>
<script>
import FormValidation from '~/libraries/form-validation';
import popupResolver from '~/libraries/popup-resolver';
import popupCloser from '~/libraries/popup-closer';
import { nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
export default {
    name: "ns-pos-note-popup",
    props: [ 'popup' ],
    data() {
        return {
            validation: new FormValidation,
            fields: [
                {
                    label: __( 'Note' ),
                    name: 'note',
                    value: '',
                    description: __( 'More details about this order' ),
                    type: 'textarea',
                }, {
                    label: __( 'Display On Receipt' ),
                    name: 'note_visibility',
                    value: '',
                    options: [{
                        label: __( 'Yes' ),
                        value: 'visible',
                    }, {
                        label: __( 'No' ),
                        value: 'hidden'
                    }],
                    description: __( 'Will display the note on the receipt' ),
                    type: 'switch',
                }
            ]
        }
    },
    mounted() {
        this.popupCloser();
        this.fields.forEach( field => {
            if ( field.name === 'note' ) {
                field.value     =   this.popup.params.note;
            } else if ( field.name === 'note_visibility' ) {
                field.value     =   this.popup.params.note_visibility;
            }
        });
    },
    methods: {
        __,
        popupResolver,
        popupCloser,

        closePopup() {
            this.popupResolver( false );
        },

        saveNote() {
            if ( ! this.validation.validateFields( this.fields ) ) {
                const errors    =   this.validation.validateFieldsErrors( this.fields );
                this.validation.triggerFieldsErrors( this.fields, errors );
                this.$forceUpdate();
                return nsSnackBar.error( __( 'Unable to proceed the form is not valid.' ) ).subscribe();
            }

            return this.popupResolver( this.validation.extractFields( this.fields ) );
        }
    }
}
</script>