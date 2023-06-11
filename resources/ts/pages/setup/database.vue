<template>
    <div class="bg-white rounded shadow my-4">
        <div class="welcome-box border-b border-gray-300 p-3 text-gray-700">
            <div class="md:-mx-4 md:flex">
                <div class="md:px-4 md:w-1/2 w-full">
                    <ns-field v-for="( field, key ) of firstPartFields" v-bind:key="key" :field="field"
                        @change="form.validateField( field )">
                        <span>{{ field.label }}</span>
                        <template slot="description">{{ field.description }}</template>
                    </ns-field>
                </div>
                <div class="md:px-4 md:w-1/2 w-full">
                    <ns-field v-for="( field, key ) of secondPartFields" v-bind:key="key" :field="field"
                        @change="form.validateField( field )">
                        <span>{{ field.label }}</span>
                        <template slot="description">{{ field.description }}</template>
                    </ns-field>
                </div>
            </div>
        </div>
        <div class="bg-gray-200 p-3 flex justify-end">
            <ns-button @click="validate()" type="info">Save Database</ns-button>
        </div>
    </div>
</template>

<script>
import FormValidation from './../../libraries/form-validation';
import { nsRouter } from './../../setup';

export default {
    data: () => ({
        form: new FormValidation,
        firstPartFields: [],
        secondPartFields: [],
        fields: [],
    }),
    methods: {
        validate() {
            if (
                this.form.validateFields( this.firstPartFields ) &&
                this.form.validateFields( this.secondPartFields )
            ) {
                this.form.disableFields( this.firstPartFields );
                this.form.disableFields( this.secondPartFields );

                const form      =   {
                    ...this.form.getValue( this.firstPartFields ),
                    ...this.form.getValue( this.secondPartFields ),
                }

                const operation  =   this.checkDatabase( form );

                operation.subscribe(
                    result => {
                        this.form.enableFields( this.firstPartFields );
                        this.form.enableFields( this.secondPartFields );

                        nsRouter.push( '/configuration' );
                        nsSnackBar.success( result.message, 'OKAY', { duration: 5000 }).subscribe();
                    },
                    error => {
                        this.form.enableFields( this.firstPartFields );
                        this.form.enableFields( this.secondPartFields );

                        nsSnackBar.error( error.message, 'OKAY' ).subscribe();
                    }
                );
            }
        },

        checkDatabase( fields ) {
            return nsHttpClient.post( `/api/nexopos/v4/setup/database`, fields );
        },
        sliceRange( entries, slices, index ) {
            const length    =   entries.length;
            const part      =   Math.ceil( length / slices );
            return entries.splice( index * part, part );
        }
    },
    mounted() {
        const fields     =   this.form.createFields([
            {
                label: 'Driver',
                description: 'Set the database driver',
                name: 'database_driver',
                value : 'mysql',
                type: 'select',
                options: [{
                    label: 'MySQL',
                    value: 'mysql',
                }, {
                    label: 'Postgres',
                    value: 'pgsql',
                }, {
                    label: 'SQLite',
                    value: 'sqlite',
                }],
                validation: 'required',
            }, {
                label: 'Hostname',
                description: 'Provide the database hostname',
                name: 'hostname',
                value : 'localhost',
            }, {
                label: 'Username',
                description: 'Username required to connect to the database.',
                name: 'username',
                value : 'root',
            }, {
                label: 'Password',
                description: 'The username password required to connect.',
                name: 'password',
                value : '',
            }, {
                label: 'Database Name',
                description: 'Provide the database name. Leave empty to use default file for SQLite Driver.',
                name: 'database_name',
                value : 'nexopos_v4',
            }, {
                label: 'Database Prefix',
                description: 'Provide the database prefix.',
                name: 'database_prefix',
                value : 'ns_',
                validation: 'required',
            }, {
                label: 'Port',
                description: 'Provide the hostname port.',
                name: 'database_port',
                value : '3306',
                validation: 'required',
            }
        ]);

        this.firstPartFields        =   Object.values( this.sliceRange( [...fields], 2, 0 ) );
        this.secondPartFields       =   Object.values( this.sliceRange( [...fields], 2, 1 ) );
    }
}
</script>

<style>

</style>