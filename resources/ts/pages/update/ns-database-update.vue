<template>
    <div class="container mx-auto flex-auto items-center justify-center flex">
        <div id="database-update" class="w-full md:w-2/3 lg:w-1/3">
            <div class="flex justify-center items-center py-6">
                <img class="w-32" src="/svg/nexopos-variant-1.svg" alt="NexoPOS">
            </div>
            <div class="my-3 rounded shadow ns-box">
                <div class="border-b ns-box-header py-4 flex items-center justify-center">
                    <h3 class="text-xl font-bold">{{ __( 'Database Update' ) }}</h3>
                </div>
                <div class="p-2 ns-box-body">
                    <p class="text-center text-sm py-4">{{ __( 'In order to keep NexoPOS running smoothly with updates, we need to proceed to the database migration. In fact you don\'t need to do any action, just wait until the process is done and you\'ll be redirected.' ) }}</p>
                    <div v-if="error" class="border-l-4 text-sm ns-notice error p-4">
                        <p>
                            {{ __( 'Looks like an error has occurred during the update. Usually, giving another shot should fix that. However, if you still don\'t get any chance.' ) }}
                            {{ __( 'Please report this message to the support : ' ) }}
                        </p>
                        <pre class="rounded whitespace-pre-wrap my-2 p-2">{{ lastErrorMessage }}</pre>
                    </div>
                </div>
                <div class="border-t ns-box-footer p-2 flex justify-between">
                    <div>
                        <ns-button v-if="error" @click="proceedUpdate()" type="error" class="rounded shadow-inner">
                            <i class="las la-sync"></i>
                            <span class="ml-1">{{ __( 'Try Again' ) }}</span>
                        </ns-button>
                    </div>
                    <div class="flex">
                        <ns-button type="info" v-if="updating" class="rounded shadow-inner">
                            <i class="las la-sync animate-spin"></i>
                            <span v-if="! updatingModule">{{ __( 'Updating' ) }}...</span>
                            <span class="mr-1" v-if="! updatingModule">{{ index }}/{{ files.length }}</span>
                            <span v-if="updatingModule">{{ __( 'Updating Modules' ) }}...</span>
                            <span class="mr-1" v-if="updatingModule">{{ index }}/{{ totalModules }}</span>
                        </ns-button>
                        <ns-button type="info" :href="returnLink" v-if="! updating" class="rounded shadow-inner">
                            <i class="las la-undo"></i>
                            <span class="ml-1">{{ __( 'Return' ) }}</span>
                        </ns-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
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
    computed: {
        totalModules() {
            return Object.values( this.modules ).length;
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
        __,
        async proceedUpdate() {
            this.updating   =   true;

            for( let index in this.files ) {
                try {
                    this.index      =   ( parseInt( index ) + 1 );
                    const response  =   await new Promise( ( resolve, reject ) => {
                        nsHttpClient.post( '/api/update', {
                            file: this.files[ index ]
                        }, {
                            headers: {
                                'X-XSRF-TOKEN'  : this.xXsrfToken
                            }
                        }).subscribe({
                            next: resolve,
                            error: reject
                        });
                    });
                } catch( exception ) {
                    this.updating           =   false;
                    this.error              =   true;
                    this.lastErrorMessage   =   exception.message || __( 'An unexpected error occurred' );

                    return nsSnackBar.error( exception.message ).subscribe();
                }
            }

            this.index                  =   0;

            if ( Object.values( this.modules ).length > 0 ) {
                this.updatingModule     =   true;
                let iterator            =   0;

                for( let index in this.modules ) {
                    try {
                        iterator        +=  1;
                        this.index      =   iterator;
                        const response  =   await new Promise( ( resolve, reject ) => {
                            nsHttpClient.post( '/api/update', {
                                module: this.modules[ index ]
                            }, {
                                headers: {
                                    'X-XSRF-TOKEN'  : this.xXsrfToken
                                }
                            }).subscribe({
                                next:resolve,
                                error:reject
                            });
                        });
                    } catch( exception ) {
                        this.updating           =   false;
                        this.error              =   true;
                        this.lastErrorMessage   =   exception.message || __( 'An unexpected error occurred' );

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
