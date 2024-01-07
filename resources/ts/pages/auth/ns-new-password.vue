<template>
    <div class="bg-white rounded shadow overflow-hidden transition-all duration-100">
        <div class="p-3 -my-2">
            <div class="py-2 fade-in-entrance anim-duration-300" v-if="fields.length > 0">
                <ns-field :key="index" v-for="(field, index) of fields" :field="field"></ns-field>
            </div>
        </div>
        <div class="flex items-center justify-center py-10" v-if="fields.length === 0">
            <ns-spinner border="4" size="16"></ns-spinner>
        </div>
        <div class="flex justify-between items-center bg-gray-200 p-3">
            <div>
                <ns-button @click="submitNewPassword()" class="justify-between" type="info">
                    <ns-spinner class="mr-2" v-if="isSubitting" size="6" border="2"></ns-spinner>
                    <span>{{ __( 'Save Password' ) }}</span>
                </ns-button>
            </div>
            <div>

            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';
import { forkJoin } from 'rxjs';
import FormValidation from '~/libraries/form-validation';
import { nsHooks, nsHttpClient, nsSnackBar } from '~/bootstrap';

export default {
    name: 'ns-login',
    props: [ 'token', 'user' ],
    data() {
        return {
            fields: [],
            xXsrfToken: null,
            validation: new FormValidation,
            isSubitting: false,
        }
    },
    mounted() {
        forkJoin([
            nsHttpClient.get( '/api/fields/ns.new-password' ),
            nsHttpClient.get( '/sanctum/csrf-cookie' ),
        ])
        .subscribe( result => {
            this.fields         =   this.validation.createFields( result[0] );
            this.xXsrfToken     =   nsHttpClient.response.config.headers[ 'X-XSRF-TOKEN' ];

            /**
             * emit an event
             * when the component is mounted
             */
            setTimeout( () => nsHooks.doAction( 'ns-login-mounted', this ), 100 );
        }, ( error ) => {
            nsSnackBar.error( error.message || __( 'An unexpected error occurred.' ), __( 'OK' ), { duration: 0 }).subscribe();
        });
    },
    methods: {
        __,
        submitNewPassword() {
            const isValid   =   this.validation.validateFields( this.fields );

            if ( ! isValid ) {
                return nsSnackBar.error( __( 'Unable to proceed the form is not valid.' ) ).subscribe();
            }

            this.validation.disableFields( this.fields );

            /**
             * that will allow override and prevent submitting
             * when certain conditions are meet.
             */
            if ( nsHooks.applyFilters( 'ns-new-password-submit', true ) ) {
                this.isSubitting    =   true;
                nsHttpClient.post( `/auth/new-password/${this.user}/${this.token}`, this.validation.getValue( this.fields ), {
                    headers: {
                        'X-XSRF-TOKEN'  : this.xXsrfToken
                    }
                }).subscribe( (result) => {
                    nsSnackBar.success( result.message ).subscribe();
                    setTimeout( () => {
                        document.location   =   result.data.redirectTo;
                    }, 500 );
                }, ( error ) => {
                    this.isSubitting    =   false;
                    this.validation.enableFields( this.fields );

                    if ( error.data ) {
                        this.validation.triggerFieldsErrors( this.fields, error.data );
                    }

                    nsSnackBar.error( error.message ).subscribe();
                })
            }
        }
    }
}
</script>
