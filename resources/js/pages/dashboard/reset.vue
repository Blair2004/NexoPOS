<template>
    <div id="reset-app">
        <div id="card-header" class="flex flex-wrap">
            <div class="text-gray-700 bg-white cursor-pointer px-4 py-2 rounded-tl-lg rounded-tr-lg">
                Reset
            </div>
        </div>
        <div class="card-body bg-white rounded-br-lg rounded-bl-lg shadow">
            <div class="-mx-4 flex flex-wrap p-2">
                <div class="px-4" :key="index" v-for="(field, index) of fields">
                    <ns-field :field="field"></ns-field>
                </div>
            </div>
            <div class="card-body border-t border-gray-400 p-2 flex">
                <div>
                    <ns-button type="info" @click="submit()">Proceed</ns-button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient, nsSnackBar } from '../../bootstrap';
import FormValidation from '../../libraries/form-validation';

export default {
    name: 'ns-reset',
    props: [ 'url' ],
    methods: {
        submit() {
            if ( ! this.validation.validateFields( this.fields ) ) {
                this.$forceUpdate();
                return nsSnackBar.error( this.$slots[ 'error-form-invalid' ] ? this.$slots[ 'error-form-invalid' ][0].text : 'Invalid Form' ).subscribe(); 
            }

            const fields   =   this.validation.getValue( this.fields );

            if ( confirm( this.$slots[ 'confirm-message' ] ? this.$slots[ 'confirm-message' ][0].text : 'Would you like to proceed ?' ) ) {
                nsHttpClient.post( '/api/nexopos/v4/reset', {fields} )
                    .subscribe( result => {
                        nsSnackBar.success( result.message ).subscribe();
                    }, error => {
                        nsSnackbar.error( error.message ).subscribe();
                    })
            }
        }
    },
    data() {
        return {
            validation: new FormValidation,
            fields: [
                {
                    label: 'Choose Option',
                    name: 'mode',
                    description: 'Will apply various reset method on the system.',
                    type: 'select',
                    options: [{
                        label: 'Wipe Everything',
                        value: 'wipe_all',
                    }, {
                        label: 'Wipe + Grocery Demo',
                        value: 'wipe_plus_grocery',
                    }],
                    validation: 'required'
                }
            ]
        }
    },
}
</script>