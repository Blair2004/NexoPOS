<template>
    <div>
        <ns-spinner size="12" border="4" animation="fast" v-if="fields.length === 0"></ns-spinner>
        <div class="bg-white rounded shadow my-2" v-if="fields.length > 0">
            <div class="welcome-box border-b border-gray-300 p-3 text-gray-700">
                <div class="border-b pb-3 mb-3" v-html="__( '<strong>NexoPOS</strong> is now able to connect to the database. Start by creating the administrator account and giving a name to your installation. Once installed, this page will no longer be accessible.' )"></div>
                <div class="flex -mx-2">
                    <div class="px-2 w-full md:w-1/2">
                        <ns-field v-for="( field, key ) of divide(fields)[0]" :key="key" :field="field"></ns-field>
                    </div>
                    <div class="px-2 w-full md:w-1/2">
                        <ns-field v-for="( field, key ) of divide(fields)[1]" :key="key" :field="field"></ns-field>
                    </div>
                </div>
            </div>
            <div class="bg-gray-200 p-3 flex justify-between items-center">
                <div>
                    <ns-spinner v-if="processing" size="8" border="4"></ns-spinner>
                </div>
                <ns-button :disabled="processing" @click="saveConfiguration()" type="info">{{ __( 'Install' )}}</ns-button>
            </div>
        </div>
    </div>
</template>

<script>
import FormValidation from '~/libraries/form-validation';
import { nsHttpClient, nsSnackBar } from "~/bootstrap";
import { __ } from '~/libraries/lang';

export default {
    data: () => ({
        form: new FormValidation,
        fields: [],
        processing: false,
        steps: [],
        failure: 0,
        maxFailure: 2,
        __,
    }),
    methods: {
        validate() {
            
        },
        verifyDBConnectivity() {

        },
        saveConfiguration( fields ) {
            this.form.disableFields( this.fields );
            const form          =   this.form.getValue( this.fields );
            form.language       =   ns.language
            this.processing     =   true;
            return nsHttpClient.post( `/api/setup/configuration`, form )
                .subscribe({
                    next: result => {
                        document.location   =   '/sign-in';
                    },
                    error:  error => {
                        this.processing     =   false;
                        this.form.enableFields( this.fields );
                        this.form.triggerFieldsErrors( this.fields, error.data );
                        nsSnackBar.error( error.message, 'OK' )
                            .subscribe();
                    }
                });
        },

        divide(arr) {
            // get the middle index of the array
            var mid = Math.floor(arr.length / 2);
            // slice the array into two arrays
            var arr1 = arr.slice(0, mid);
            var arr2 = arr.slice(mid);
            // return an array of two arrays
            return [arr1, arr2];
        },

        checkDatabase() {
            nsHttpClient.get( '/api/setup/database' )
                .subscribe({
                    next: result => {
                        this.fields     =   this.form.createFields([
                            {
                                label: __( 'Application' ),
                                description: __( 'That is the application name.' ),
                                name: 'ns_store_name',
                                validation: 'required',
                            }, {
                                label: __( 'Username' ),
                                description: __( 'Provide the administrator username.' ),
                                name: 'admin_username',
                                validation: 'required',
                            }, {
                                label: __( 'Email' ),
                                description: __( 'Provide the administrator email.' ),
                                name: 'admin_email',
                                validation: 'required',
                            }, {
                                label: __( 'Password' ),
                                type: 'password',
                                description: __( 'What should be the password required for authentication.' ),
                                name: 'password',
                                validation: 'required',
                            }, {
                                label: __( 'Confirm Password' ),
                                type: 'password',
                                description: __( 'Should be the same as the password above.' ),
                                name: 'confirm_password',
                                validation: 'required',
                            }
                        ]);
                    },
                    error: error => {
                        nsRouter.push( '/database' );
                        nsSnackBar.error( 'You need to define database settings', 'OKAY', { duration: 3000 })
                            .subscribe();
                    }
                })
        }
    },
    mounted() {
        this.checkDatabase();
    }
}
</script>

<style>

</style>