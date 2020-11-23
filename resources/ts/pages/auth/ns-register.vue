<template>
    <div class="bg-white rounded shadow overflow-hidden transition-all duration-100">
        <div class="p-3 -my-2">
            <div class="py-2 fade-in-entrance anim-duration-300" v-if="fields.length > 0">
                <ns-field :key="index" v-for="(field, index) of fields" :field="field"></ns-field>
            </div>
        </div>
        <div class="flex items-center justify-center" v-if="fields.length === 0">
            <ns-spinner></ns-spinner>
        </div>
        <div class="flex w-full items-center justify-center py-4">
            <a href="/sign-in" class="hover:underline text-blue-600 text-sm">Already registered ?</a>
        </div>
        <div class="flex justify-between items-center bg-gray-200 p-3">
            <div>
                <ns-button @click="register()" type="info">Register</ns-button>
            </div>
            <div>
                <ns-link :href="'/sign-in'" type="success">Sign In</ns-link>
            </div>
        </div>
    </div>
</template>
<script>
import FormValidation from '@/libraries/form-validation';
import { nsHooks, nsHttpClient, nsSnackBar } from '@/bootstrap';
import { forkJoin } from 'rxjs';

export default {
    name: 'ns-register',
    data() {
        return {
            fields: [],
            xXsrfToken: null,
            validation: new FormValidation
        }
    },
    mounted() {
        forkJoin([
            nsHttpClient.get( '/api/nexopos/v4/fields/ns.register' ),
            nsHttpClient.get( '/sanctum/csrf-cookie' ),
        ])
        .subscribe( result => {
            this.fields         =   this.validation.createFields( result[0] );
            this.xXsrfToken     =   nsHttpClient.response.config.headers[ 'X-XSRF-TOKEN' ];

            /**
             * emit an event
             * when the component is mounted
             */
            setTimeout( () => nsHooks.doAction( 'ns-register-mounted', this ) );
        });
    },
    methods: {
        register() {
            const isValid   =   this.validation.validateFields( this.fields );            

            if ( ! isValid ) {
                // return nsSnackBar.error( 'Unable to proceed the form is not valid.' ).subscribe();
            }

            this.validation.disableFields( this.fields );

            if ( nsHooks.applyFilters( 'ns-register-submit', true ) ) {
                nsHttpClient.post( '/auth/sign-up', this.validation.getValue( this.fields ), {
                    headers: {
                        'X-XSRF-TOKEN'  : this.xXsrfToken
                    }
                }).subscribe( (result) => {
                    nsSnackBar.success( result.message ).subscribe();
                    setTimeout( () => {
                        document.location   =   result.data.redirectTo;
                    }, 1500 );
                }, ( error ) => {
                    this.validation.triggerFieldsErrors( this.fields, error );
                    this.validation.enableFields( this.fields );
                    nsSnackBar.error( error.message ).subscribe();
                })
            }
        }
    }
}
</script>