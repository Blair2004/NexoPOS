<template>
    <div class="ns-box rounded shadow overflow-hidden transition-all duration-100">
        <div class="ns-box-body">
            <div class="p-3 -my-2">
                <div class="py-2 fade-in-entrance anim-duration-300" v-if="fields.length > 0" @keyup.enter="signIn()">
                    <ns-field :key="index" v-for="(field, index) of fields" :field="field"></ns-field>
                </div>
            </div>
            <div class="flex items-center justify-center py-10" v-if="fields.length === 0">
                <ns-spinner border="4" size="16"></ns-spinner>
            </div>
            <div class="flex w-full items-center justify-center py-4" v-if="showRecoveryLink">
                <a href="/password-lost" class="hover:underline text-blue-600 text-sm">{{ __( 'Password Forgotten ?' ) }}</a>
            </div>
        </div>
        <div class="flex justify-between items-center border-t ns-box-footer p-3">
            <div>
                <ns-button :disabled="isSubitting" @click="signIn()" class="justify-between" type="info">
                    <ns-spinner class="mr-2" v-if="isSubitting" size="6"></ns-spinner>
                    <span>{{ __( 'Sign In' ) }}</span>
                </ns-button>
            </div>
            <div v-if="showRegisterButton">
                <ns-button :link="true" :href="'/sign-up'" type="success">{{ __( 'Register' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>
<script>
import { forkJoin } from 'rxjs';
import FormValidation from '~/libraries/form-validation';
import { nsHooks, nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
export default {
    name: 'ns-login',
    props: [ 'showRecoveryLink', 'showRegisterButton' ],
    data() {
        return {
            fields: [],
            xXsrfToken: null,
            validation: new FormValidation,
            isSubitting: false,
        }
    },
    mounted() {
        forkJoin({
            login: nsHttpClient.get( '/api/fields/ns.login' ),
            csrf: nsHttpClient.get( '/sanctum/csrf-cookie' ),
        })
        .subscribe({
            next: result => {
                this.fields         =   this.validation.createFields( result.login );
                this.xXsrfToken     =   nsHttpClient.response.config.headers[ 'X-XSRF-TOKEN' ];

                /**
                 * emit an event
                 * when the component is mounted
                 */
                setTimeout( () => nsHooks.doAction( 'ns-login-mounted', this ), 100 );
            },
            error: ( error ) => {
                nsSnackBar.error( error.message || __( 'An unexpected error occurred.' ), __( 'OK' ), { duration: 0 }).subscribe();
            }
        });
    },
    methods: {
        __,
        signIn() {
            const isValid   =   this.validation.validateFields( this.fields );

            if ( ! isValid ) {
                return nsSnackBar.error( __( 'Unable to proceed the form is not valid.' ) ).subscribe();
            }

            this.validation.disableFields( this.fields );

            /**
             * that will allow override and prevent submitting
             * when certain conditions are meet.
             */
            if ( nsHooks.applyFilters( 'ns-login-submit', true ) ) {
                this.isSubitting    =   true;
                nsHttpClient.post( '/auth/sign-in', this.validation.getValue( this.fields ), {
                    headers: {
                        'X-XSRF-TOKEN'  : this.xXsrfToken
                    }
                }).subscribe({
                    next: (result) => {
                        document.location   =   result.data.redirectTo;
                    },
                    error: ( error ) => {
                        this.isSubitting    =   false;
                        this.validation.enableFields( this.fields );

                        if ( error.data ) {
                            this.validation.triggerFieldsErrors( this.fields, error.data );
                        }

                    nsSnackBar.error( error.message || __( 'An unexpected error occured.' ) ).subscribe();
                    }
                })
            }
        }
    }
}
</script>
