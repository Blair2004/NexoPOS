<template>
    <div class="bg-white rounded shadow my-4" v-if="! isCheckingDatabase">
        <div class="welcome-box border-b border-gray-300 p-3 text-gray-600">
            <div class="border-b pb-4 mb-4">
                <div v-if="isMySQL">
                    <h3 class="font-bold text-lg">{{ __( 'MySQL is selected as database driver' ) }}</h3>
                    <p>{{ __( 'Please provide the credentials to ensure NexoPOS can connect to the database.' ) }}</p>
                </div>
                <div v-if="isSqlite">
                    <h3 class="font-bold text-lg">{{ __( 'Sqlite is selected as database driver' ) }}</h3>
                    <p>{{ __( 'Make sure Sqlite module is available for PHP. Your database will be located on the database directory.' ) }}</p>
                </div>
            </div>
            <div class="md:-mx-4 md:flex">
                <div class="md:px-4 md:w-1/2 w-full">
                    <template v-for="( field, key ) of firstPartFields" v-bind:key="key">
                        <ns-field v-if="field.show === undefined || ( field.show !== undefined && field.show( form ) )" :field="field"
                            @change="formValidation.validateField( field )">
                            <span>{{ field.label }}</span>
                            <slot name="description">{{ field.description }}</slot>
                        </ns-field>
                    </template>
                </div>
                <div class="md:px-4 md:w-1/2 w-full">
                    <template v-for="( field, key ) of secondPartFields" v-bind:key="key">
                        <ns-field v-if="field.show === undefined || ( field.show !== undefined && field.show( form ) )" :field="field"
                            @change="formValidation.validateField( field )">
                            <span>{{ field.label }}</span>
                            <slot name="description">{{ field.description }}</slot>
                        </ns-field>
                    </template>
                </div>
            </div>
        </div>
        <div class="bg-gray-200 p-3 flex justify-end">
            <ns-button :disabled="isLoading" @click="validate()" type="info">
                <ns-spinner v-if="isLoading" class="mr-2" :size="6"></ns-spinner>
                <span>{{ __( 'Save Settings' ) }}</span>
            </ns-button>
        </div>
    </div>
    <div v-if="isCheckingDatabase" class="bg-white shadow rounded p-3 flex justify-center items-center">
        <div class="flex items-center"><ns-spinner :size="10"></ns-spinner><span class="ml-3">{{ __( 'Checking database connectivity...' ) }}</span></div>
    </div>
</template>

<script>
import FormValidation from '~/libraries/form-validation';
import { __ } from '~/libraries/lang';

export default {
    data: () => ({
        formValidation: new FormValidation,
        firstPartFields: [],
        secondPartFields: [],
        fields: [],
        isLoading: false,
        isCheckingDatabase: false,
        __,
    }),
    computed: {
        form() {
            return this.formValidation.extractFields([
                ...this.firstPartFields,
                ...this.secondPartFields
            ]);
        },
        isMySQL() {
            return this.form.database_driver === 'mysql';
        },
        isMariaDB() {
            return this.form.database_driver === 'mariadb';
        },
        isSqlite() {
            return this.form.database_driver === 'sqlite';
        }
    },
    methods: {
        validate() {
            if (
                this.formValidation.validateFields( this.firstPartFields ) &&
                this.formValidation.validateFields( this.secondPartFields )
            ) {
                this.isLoading   =  true;
                this.formValidation.disableFields( this.firstPartFields );
                this.formValidation.disableFields( this.secondPartFields );

                const form      =   {
                    ...this.formValidation.getValue( this.firstPartFields ),
                    ...this.formValidation.getValue( this.secondPartFields ),
                }

                const operation  =   this.checkDatabase( form );

                operation.subscribe(
                    result => {
                        this.formValidation.enableFields( this.firstPartFields );
                        this.formValidation.enableFields( this.secondPartFields );

                        nsRouter.push( 'configuration' );
                        nsSnackBar.success( result.message, __( 'OKAY' ), { duration: 5000 }).subscribe();
                    },
                    error => {
                        this.formValidation.enableFields( this.firstPartFields );
                        this.formValidation.enableFields( this.secondPartFields );
                        this.isLoading   =  false;

                        nsSnackBar.error( error.message, __( 'OKAY' ) ).subscribe();
                    }
                );
            }
        },

        checkDatabase( fields ) {
            return nsHttpClient.post( `/api/setup/database`, fields );
        },
        checkExisting() {
            return nsHttpClient.get( `/api/setup/check-database` );
        },
        sliceRange( entries, slices, index ) {
            const length    =   entries.length;
            const part      =   Math.ceil( length / slices );
            return entries.splice( index * part, part );
        },
        loadFields() {
            this.fields     =   this.formValidation.createFields([
                {
                    label: __( 'Driver' ),
                    description: __( 'Set the database driver'),
                    name: 'database_driver',
                    value : 'mysql',
                    type: 'select',
                    options: [{
                        label: 'MySQL',
                        value: 'mysql',
                    }, {
                        label: 'MariaDB',
                        value: 'mariadb',
                    }, {
                        label: 'SQLite',
                        value: 'sqlite',
                    }],
                    validation: 'required',
                }, {
                    label: __( 'Hostname' ),
                    description: __( 'Provide the database hostname' ),
                    name: 'hostname',
                    value : 'localhost',
                    show: ( form ) => {
                        return [ 'mysql', 'mariadb' ].includes( form.database_driver );
                    }
                }, {
                    label: __( 'Username' ),
                    description: __( 'Username required to connect to the database.' ),
                    name: 'username',
                    value : 'root',
                    show: ( form ) => {
                        return [ 'mysql', 'mariadb' ].includes( form.database_driver );
                    }
                }, {
                    label: __( 'Password' ),
                    description: __( 'The username password required to connect.' ),
                    name: 'password',
                    value : '',
                    show: ( form ) => {
                        return [ 'mysql', 'mariadb' ].includes( form.database_driver );
                    }
                }, {
                    label: __( 'Database Name' ),
                    description: __( 'Provide the database name. Leave empty to use default file for SQLite Driver.' ),
                    name: 'database_name',
                    value : 'nexopos_v4',
                    show: ( form ) => {
                        return [ 'mysql', 'mariadb' ].includes( form.database_driver );
                    }
                }, {
                    label: __( 'Database Prefix' ),
                    description: __( 'Provide the database prefix.' ),
                    name: 'database_prefix',
                    value : 'ns_',
                    validation: 'required',
                    show: ( form ) => {
                        return [ 'mysql', 'mariadb' ].includes( form.database_driver );
                    }
                }, {
                    label: __( 'Port' ),
                    description: __( 'Provide the hostname port.' ),
                    name: 'database_port',
                    value : '3306',
                    validation: 'required',
                    show: ( form ) => {
                        return [ 'mysql', 'mariadb' ].includes( form.database_driver );
                    }
                }
            ]);

            this.firstPartFields        =   Object.values( this.sliceRange( [...this.fields], 2, 0 ) );
            this.secondPartFields       =   Object.values( this.sliceRange( [...this.fields], 2, 1 ) );
        }
    },
    mounted() {
        this.isCheckingDatabase     =   true;
        this.checkExisting().subscribe({
            next: result => {
                nsRouter.push( 'configuration' );
            },
            error: error => {
                this.isCheckingDatabase =   false;
                this.loadFields();
            }
        })
        
    }
}
</script>

<style>

</style>