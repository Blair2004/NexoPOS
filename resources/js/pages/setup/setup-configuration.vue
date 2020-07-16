<template>
    <div>
        <ns-spinner size="12" border="4" animation="fast" v-if="! fields"></ns-spinner>
        <div class="bg-white rounded shadow my-2" v-if="fields">
            <div class="welcome-box border-b border-gray-300 p-3 text-gray-700">
                <ns-input v-for="( field, key ) of fields" v-bind:key="key" :field="field" 
                    @change="form.validateField( field )">
                    <span>{{ field.label }}</span>
                    <template slot="description">{{ field.description }}</template>
                </ns-input>
            </div>
            <div class="bg-gray-200 p-3 flex justify-between items-center">
                <div>
                    <ns-spinner v-if="processing" size="8" border="4"></ns-spinner>
                </div>
                <ns-button :disabled="processing" @click="saveConfiguration()" type="info">Create Installation</ns-button>
            </div>
        </div>
    </div>
</template>

<script>
import FormValidation from './../../libraries/form-validation';
import { nsHttpClient, nsSnackBar } from "./../../bootstrap";
import { nsRouter } from './../../setup';

export default {
    data: () => ({
        form: new FormValidation,
        fields: [],
        processing: false,
        steps: [],
    }),
    methods: {
        validate() {
            
        },
        verifyDBConnectivity() {

        },
        saveConfiguration( fields ) {
            this.form.disableFields( this.fields );
            this.processing     =   true;
            return nsHttpClient.post( `/api/nexopos/v4/setup/configuration`, this.form.getValue( this.fields ) )
                .subscribe( result => {

                });
        }
    },
    mounted() {
        nsHttpClient.get( '/api/nexopos/v4/setup/database' )
            .subscribe( result => {
                this.fields     =   this.form.createForm([
                    {
                        label: 'Application',
                        description: 'what is the application name',
                        name: 'app_name',
                        validation: 'required',
                    }, {
                        label: 'Username',
                        description: 'Provide the administrator username.',
                        name: 'admin_username',
                        validation: 'required',
                    }, {
                        label: 'Email',
                        description: 'Provide the administrator email.',
                        name: 'admin_email',
                        validation: 'required',
                    }, {
                        label: 'Password',
                        type: 'password',
                        description: 'What should be the password required for authentication.',
                        name: 'password',
                        validation: 'required',
                    }, {
                        label: 'Confirm Password',
                        type: 'password',
                        description: 'Should be the same as the password above.',
                        name: 'confirm_password',
                        validation: 'required',
                    }
                ]);
            }, error => {
                nsRouter.push( '/database' );
                nsSnackBar.error( 'You need to define database settings', 'OKAY', { duration: 3000 })
                    .subscribe();
            })
    }
}
</script>

<style>

</style>