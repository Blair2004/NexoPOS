import { timeStamp } from 'console';
import { forkJoin } from 'rxjs';
import Vue from 'vue';
import { nsHttpClient, nsSnackBar } from './bootstrap';
import * as components from './components/components';
import FormValidation from './libraries/form-validation';

console.log( components );

new Vue({
    el: '#nexopos-authentication',
    components : { ...components },
    data() {
        return {
            fields: [],
            xXsrfToken: null,
            validation: new FormValidation
        }
    },
    mounted() {
        forkJoin([
            nsHttpClient.get( '/api/nexopos/v4/fields/ns.auth' ),
            nsHttpClient.get( '/sanctum/csrf-cookie' ),
        ])
        .subscribe( result => {
            this.fields         =   this.validation.createFields( result[0] );
            this.xXsrfToken     =   nsHttpClient.response.config.headers[ 'X-XSRF-TOKEN' ];
        });
    },
    methods: {
        signIn() {
            const isValid   =   this.validation.validateFields( this.fields );            

            if ( ! isValid ) {
                return nsSnackBar.error( 'Unable to proceed the form is not valid.' ).subscribe();
            }

            this.validation.disableFields( this.fields );

            nsHttpClient.post( '/auth/sign-in', this.validation.getValue( this.fields ), {
                headers: {
                    'X-XSRF-TOKEN'  : this.xXsrfToken
                }
            }).subscribe( (result: any) => {
                document.location   =   result.data.redirectTo;
            }, ( error ) => {
                this.validation.enableFields( this.fields );
                nsSnackBar.error( error.message ).subscribe();
            })
        }
    }
});