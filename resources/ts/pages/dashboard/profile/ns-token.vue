<template>
    <div class="-mx-2">
        <div class="px-2 w-full md:w-1/2">
            <div class="mb-4">
                <ns-notice>
                    <template #title>{{ __( 'About Token' ) }}</template>
                    <template #description>{{ __( `Token are used to provide a secure access to NexoPOS resources without having to share your personal username and password.
                       Once generated, they won\'t expires until you explicitely revoke it.` ) }}
                    </template>
                </ns-notice>
            </div>
            <div class="my-2">
                <ns-field v-for="(field,index) of fields" :key="index" :field="field"></ns-field>
                <div class="flex justify-end">
                    <ns-button @click="createToken()" type="info">{{ __( 'Save Token' ) }}</ns-button>
                </div>
            </div>
            <div>
                <h3 class="py-2 border-b-4 text-center border-box-edge text-xl">{{ __( 'Generated Tokens' ) }}</h3>
                <ul v-if="tokens.length > 0 && ! isLoading">
                    <li v-for="(token, index) of tokens" :key="index" class="p-2 border-b flex justify-between items-center border-box-edge">
                        <div class="flex flex-col">
                            <h4 class="text-lg">{{ token.name }}</h4>
                            <div>
                                <ul>
                                    <li><span class="text-xs text-tertiary">{{ __( 'Created' ) }}: {{ token.created_at }}</span></li>
                                    <li><span class="text-xs text-tertiary">{{ __( 'Last Use' ) }}: {{ token.last_used_at || __( 'Never' ) }}</span></li>
                                    <li><span class="text-xs text-tertiary">{{ __( 'Expires' ) }}: {{ token.expires_at || __( 'Never' ) }}</span></li>
                                </ul>
                            </div>
                        </div>
                        <div>
                            <ns-close-button @click="revokeToken( token )" class="px-2">{{ __( 'Revoke' ) }}</ns-close-button>
                        </div>
                    </li>
                </ul>
                <div v-if="isLoading" class="my-4">
                    <ns-spinner></ns-spinner>
                </div>
                <div class="mt-2" v-if="! isLoading && tokens.length === 0">
                    <div class="text-center text-tertiary text-sm my-4">
                        {{ __( 'You haven\'t yet generated any token for your account. Create one to get started.' ) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { nsConfirmPopup } from '~/components/components';
import FormValidation from '~/libraries/form-validation';
import { __ } from '~/libraries/lang';
import nsTokenOutputPopupVue from '~/popups/ns-token-output-popup.vue';
export default {
    name: 'ns-token',
    data() {
        return {
            validation: new FormValidation,
            tokens: [],
            isLoading: false,
            fields: [
                {
                    type: 'text',
                    label: __( 'Token Name' ),
                    name: 'name',
                    description: __( 'This will be used to identifier the token.' ),
                    validation: 'required'
                }
            ]
        }
    },
    methods: {
        __,
        createToken() {
            if ( ! this.validation.validateFields( this.fields ) ) {
                return nsSnackBar.error( __( 'Unable to proceed, the form is not valid.' ) ).subscribe();
            }

            nsHttpClient.post( `/api/users/create-token`, this.validation.extractFields( this.fields ) )
                .subscribe( async (result) => {
                    try {
                        await new Promise ( ( resolve, reject ) => {
                            Popup.show( nsTokenOutputPopupVue, { resolve, reject, result })
                        });

                        this.loadTokens();
                    } catch( exception ) {
                        console.log( exception );
                    }
                })
        },
        revokeToken( token ) {
            Popup.show( nsConfirmPopup, {
                title: __( 'Confirm Your Action' ),
                message: __( `You're about to delete a token that might be in use by an external app. Deleting will prevent that app from accessing the API. Would you like to proceed ?` ),
                onAction: ( action ) => {
                    if ( action ) {
                        nsHttpClient.delete( `/api/users/tokens/${token.id}` )
                            .subscribe({
                                next: result => {
                                    this.loadTokens();
                                    nsSnackBar.success( result.message ).subscribe();
                                },
                                error: error => {
                                    nsSnackBar.error( error.message || 'An unexpected error occured.' ).subscribe();
                                }
                            })
                    }
                }
            })
        },
        loadTokens() {
            this.isLoading  =   true;
            nsHttpClient.get( `/api/users/tokens` )
                .subscribe({
                    next: tokens => {
                        this.isLoading  =   false;
                        this.tokens     =   tokens;
                    },
                    error: error => {
                        nsSnackBar.error( 'Unable to load the token. An unexpected error occured.' ).subscribe();
                    }
                })
        }
    },
    mounted() {
        this.fields     =   this.validation.createFields( this.fields );

        this.loadTokens();
    }
}
</script>