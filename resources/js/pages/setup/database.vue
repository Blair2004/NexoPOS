<template>
    <div class="bg-white rounded shadow my-2">
        <div class="welcome-box border-b border-gray-300 p-3 text-gray-700">
            <ns-input v-for="( field, key ) of fields" v-bind:key="key" :field="field" 
                @change="form.validateField( field )">
                <span>{{ field.label }}</span>
                <template slot="description">{{ field.description }}</template>
            </ns-input>
        </div>
        <div class="bg-gray-200 p-3 flex justify-end">
            <ns-button @click="validate()" type="info">Save Database</ns-button>
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
        fields: []
    }),
    methods: {
        validate() {
            if ( this.form.validateFields( this.fields ) ) {
                this.form.disableFields( this.fields );
                const operation  =   this.checkDatabase( this.form.getValue( this.fields ) );
                operation.subscribe( 
                    result => {
                        this.form.enableFields( this.fields );
                        nsRouter.push( '/configuration' );
                        nsSnackBar.success( result.message, 'OKAY', { duration: 5000 }).subscribe();
                    }, 
                    error => {
                        this.form.enableFields( this.fields );
                        nsSnackBar.error( error.response.data.message, 'OKAY' ).subscribe();
                    }
                );
            }
            console.log( 'not valid' );
        },
        checkDatabase( fields ) {
            return nsHttpClient.post( `/api/nexopos/v4/setup/database`, fields );
        }
    },
    mounted() {
        this.fields     =   this.form.createFields([
            {
                label: 'Hostname',
                description: 'Provide the database hostname',
                name: 'hostname',
                value : 'localhost',
                validation: 'required',
            }, {
                label: 'Username',
                description: 'Username required to connect to the database.',
                name: 'username',
                value : 'root',
                validation: 'required',
            }, {
                label: 'Password',
                description: 'The username password required to connect.',
                name: 'password',
                value : '',
            }, {
                label: 'Database Name',
                description: 'Provide the database name.',
                name: 'database_name',
                value : 'nexopos_v4',
                validation: 'required',
            }, {
                label: 'Database Name',
                description: 'Provide the database name.',
                name: 'database_prefix',
                value : 'ns_',
                validation: 'required',
            }
        ]);
    }
}
</script>

<style>

</style>