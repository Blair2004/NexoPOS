import { timeStamp } from 'console';
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
        nsHttpClient.get( '/sanctum/csrf-cookie' )
            .subscribe( result => {
                this.xXsrfToken     =   nsHttpClient.response.config.headers[ 'X-XSRF-TOKEN' ];
            });
        
        this.fields     =   this.validation.createFields([
            {
                'label'         :   'Username',
                'type'          :   'text',
                'name'          :   'username',
                'description'   :   'Provide your actual username',
                'validation'    :   'required'
            }, {
                'label'         :   'Password',
                'type'          :   'password',
                'name'          :   'password',
                'description'   :   'Provide your secured password.',
                'validation'    :   'required'
            }
        ]);
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