<template>
    <div class="container mx-auto flex-auto items-center justify-center flex">
        <div id="sign-in-box" class="w-full md:w-2/3 lg:w-1/3">
            <div class="flex justify-center items-center py-6">
                <h2 class="text-6xl font-bold text-transparent bg-clip-text from-blue-500 to-indigo-500 bg-gradient-to-br">NexoPOS</h2>
            </div>
            <div class="my-3 rounded shadow bg-white">
                <div class="border-b border-gray-200 py-4 flex items-center justify-center">
                    <h3 class="text-xl font-bold text-gray-700">Datebase Update</h3>
                </div>
                <div class="p-2">
                    <p class="text-center text-sm text-gray-600 py-4">In order to keep NexoPOS running smoothly with updates, we need to proceed to the database migration. In fact you don't need to do any action, just wait until the process is done and you'll be redirected.</p>
                    <div v-if="error" class="border-l-4 text-sm border-red-600 bg-red-200 p-4 text-gray-700">
                        <p>
                            Looks like an error has occured during the update. Usually, giving another shot should fix that. However, if you still don't get any chance.
                        Please report this message to the support : 
                        </p>
                        <pre class="rounded whitespace-pre-wrap bg-gray-700 text-white my-2 p-2">{{ lastErrorMessage }}</pre>
                    </div>
                </div>
                <div class="border-t border-gray-200 p-2 flex justify-between">
                    <div>
                        <button v-if="error" @click="proceedUpdate()" class="rounded bg-red-400 shadow-inner text-white p-2">
                            <i class="las la-sync"></i>
                            <span>Try Again</span>
                        </button>
                    </div>
                    <div class="flex">
                        <button v-if="updating" class="rounded bg-blue-400 shadow-inner text-white p-2">
                            <i class="las la-sync animate-spin"></i>
                            <span v-if="! updatingModule">Updating...</span>
                            <span class="mr-1" v-if="! updatingModule">{{ index }}/{{ files.length }}</span>
                            <span v-if="updatingModule">Updating Modules...</span>
                            <span class="mr-1" v-if="updatingModule">{{ index }}/{{ modules.length }}</span>
                        </button>
                        <a :href="returnLink" v-if="! updating" class="rounded bg-blue-400 shadow-inner text-white p-2">
                            <i class="las la-undo"></i>
                            <span>Return</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '@/libraries/lang';
import { nsHttpClient, nsSnackBar } from '../../../ts/bootstrap';
export default {
    name: 'ns-database-update',
    data() {
        return {
            files: Update.files,
            returnLink: Update.returnLink,
            modules: Update.modules,
            updating: false,
            xXsrfToken: null,
            updatingModule: false,
            error: false,
            lastErrorMessage: '',
            index: 0,
        }
    },
    mounted() {
        nsHttpClient.get( '/sanctum/csrf-cookie' )
            .subscribe( _ => {
                try {
                    this.xXsrfToken     =   nsHttpClient.response.config.headers[ 'X-XSRF-TOKEN' ];
                    this.proceedUpdate()
                } catch( e ) {
                    nsSnackBar.error( e.message ).subscribe();
                }
            })
    },
    methods: {
        async proceedUpdate() {
            this.updating   =   true;

            for( let index in this.files ) {
                try {
                    this.index      =   ( parseInt( index ) + 1 );
                    const response  =   await new Promise( ( resolve, reject ) => {
                        nsHttpClient.post( '/api/nexopos/v4/update', {
                            file: this.files[ index ]
                        }, {
                            headers: {
                                'X-XSRF-TOKEN'  : this.xXsrfToken
                            }
                        }).subscribe( resolve, reject );
                    });
                } catch( exception ) {
                    this.updating           =   false;
                    this.error              =   true;
                    this.lastErrorMessage   =   exception.message || __( 'An unexpected error occured' );

                    return nsSnackBar.error( exception.message ).subscribe();
                }
            }

            if ( Object.values( this.modules ).length > 0 ) {
                this.updatingModule     =   true;

                for( let index in this.modules ) {
                    try {
                        this.index      =   ( parseInt( index ) + 1 );
                        const response  =   await new Promise( ( resolve, reject ) => {
                            nsHttpClient.post( '/api/nexopos/v4/update', {
                                module: this.modules[ index ]
                            }, {
                                headers: {
                                    'X-XSRF-TOKEN'  : this.xXsrfToken
                                }
                            }).subscribe( resolve, reject );
                        });
                    } catch( exception ) {
                        this.updating           =   false;
                        this.error              =   true;
                        this.lastErrorMessage   =   exception.message || __( 'An unexpected error occured' );

                        return nsSnackBar.error( exception.message ).subscribe();
                    }
                }
            }

            this.error          =   false;
            this.updating       =   false;
            document.location   =   this.returnLink;
        }
    }
}
</script>