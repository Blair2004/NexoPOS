<template>
    <div id="reset-app" class="ns-tab">
        <div id="card-header" class="flex flex-wrap">
            <div class="tab active cursor-pointer px-4 py-2 rounded-tl-lg rounded-tr-lg">
                {{ __( 'Reset' ) }}
            </div>
        </div>
        <div class="card-body ns-tab-item rounded-br-lg rounded-bl-lg">
            <div class="shadow rounded">
                <div class="-mx-4 flex flex-wrap p-2">
                    <div class="px-4" :key="index" v-for="(field, index) of fields">
                        <ns-field :field="field"></ns-field>
                    </div>
                </div>
                <div class="card-body p-2 flex">
                    <div> 
                        <ns-button type="info" @click="submit()">{{ __( 'Proceed' ) }}</ns-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import FormValidation from '~/libraries/form-validation';

export default {
    name: 'ns-reset',
    props: [ 'url' ],
    methods: {
        __,
        submit() {
            if ( this.fields.length === 0 ) {
                return nsSnackBar.error( __( 'This form is not completely loaded.' ) ).susbcribe();
            }

            if ( ! this.validation.validateFields( this.fields ) ) {
                this.$forceUpdate();
                return nsSnackBar.error( __( 'Unable to proceed the form is not valid.' ) ).subscribe(); 
            }

            const fields   =   this.validation.getValue( this.fields );

            if ( confirm( __( 'Would you like to proceed ?' ) ) ) {
                nsHttpClient.post( '/api/reset', fields )
                    .subscribe({
                        next: result => {
                            nsSnackBar.success( result.message ).subscribe();
                        },
                        error: error => {
                            nsSnackBar.error( error.message ).subscribe();
                        }
                    })
            }
        },
        loadFields() {
            nsHttpClient.get( '/api/fields/ns.reset' )
                .subscribe({
                    next: fields => {
                        this.fields     =   this.validation.createFields( fields );
                    },
                    error: error => {
                        nsSnackBar.error( error.message ).subscribe();
                    }
                })
        }
    },
    mounted() {
        this.loadFields();
    },
    data() {
        return {
            validation: new FormValidation,
            fields: [
                // ...
            ]
        }
    },
}
</script>