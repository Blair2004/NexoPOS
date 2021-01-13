<template>
    <div class="shadow-lg bg-white w-95vw md:w-3/5-screen lg:w-2/5-screen">
        <div class="p-2 flex justify-between items-center border-b border-gray-200">
            <h3 class="font-bold">Order Note</h3>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="p-2 border-b border-gray-200">
            <ns-field v-for="(field, index) of fields" :key="index" :field="field"></ns-field>
        </div>
        <div class="p-2 flex justify-end">
            <ns-button type="info" @click="saveNote()">Save</ns-button>
        </div>
    </div>
</template>
<script>
import FormValidation from '@/libraries/form-validation';
import popupResolver from '@/libraries/popup-resolver';
import popupCloser from '@/libraries/popup-closer';
import { nsSnackBar } from '@/bootstrap';
export default {
    name: "ns-pos-note-popup",
    data() {
        return {
            validation: new FormValidation,
            fields: [
                {
                    label: 'Note',
                    name: 'note',
                    value: '',
                    description: 'More details about this order',
                    type: 'textarea',
                }, {
                    label: 'Display On Receipt',
                    name: 'note_visibility',
                    value: '',
                    options: [{
                        label: 'Yes',
                        value: 'visible',
                    }, {
                        label: 'No',
                        value: 'hidden'
                    }],
                    description: 'Will display the note on the receipt',
                    type: 'switch',
                }
            ]
        }
    },
    mounted() {
        this.popupCloser();
        this.fields.forEach( field => {
            if ( field.name === 'note' ) {
                field.value     =   this.$popupParams.note;
            } else if ( field.name === 'note_visibility' ) {
                field.value     =   this.$popupParams.note_visibility;
            }
        });
    },
    methods: {
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
                console.log( this.fields );
                return nsSnackBar.error( 'Unable to proceed the form is not valid.' ).subscribe();
            }

            return this.popupResolver( this.validation.extractFields( this.fields ) );
        }
    }
}
</script>