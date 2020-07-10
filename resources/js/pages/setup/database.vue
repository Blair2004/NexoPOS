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

export default {
    data: () => ({
        form: new FormValidation,
        fields: []
    }),
    methods: {
        validate() {
            if ( this.form.validateForm( this.fields ) ) {
               return console.log( 'form valid' )
            }
            console.log( 'not valid' );
        }
    },
    mounted() {
        this.fields     =   this.form.createForm([
            {
                label: 'Hostname',
                description: 'Provide the database hostname',
                name: 'hostname',
                value: 'Hello',
                validation: 'required',
            }, {
                label: 'Username',
                description: 'Username required to connect to the database.',
                name: 'username',
                validation: 'required',
            }, {
                label: 'Password',
                description: 'The username password required to connect.',
                name: 'password',
                validation: 'required',
            }, {
                label: 'Database Name',
                description: 'Provide the database name.',
                name: 'database_name',
                validation: 'required',
            }
        ]);
    }
}
</script>

<style>

</style>