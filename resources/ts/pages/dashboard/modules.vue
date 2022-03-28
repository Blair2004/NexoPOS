<template>
    <div id="module-wrapper" class="flex-auto flex flex-col pb-4">
        <div class="flex justify-between items-center">
            <div class="flex justify-between items-center -mx-2">
                <span class="px-2">
                    <div class="ns-button">
                        <a @click="refreshModules()" class="items-center justify-center rounded cursor-pointer shadow flex px-3 py-1 ">
                            <i class="las la-sync"></i>
                            <span class="mx-2">{{ __( 'Refresh' ) }}</span>
                        </a>
                    </div>
                </span>
                <span class="px-2">
                    <div class="ns-button">
                        <a :href="upload" class="flex items-center justify-center rounded cursor-pointer shadow px-3 py-1">
                            <span>{{ __( 'Upload' ) }}</span>                        
                            <i class="las la-angle-right"></i>
                        </a>
                    </div>
                </span>
            </div>
            <div class="header-tabs flex -mx-4 flex-wrap">
                <div class="px-4 text-xs text-blue-500 font-semibold hover:underline"><a href="#">{{ $slots[ 'enabled' ] ? $slots[ 'enabled' ][0].text : __( 'Enabled' ) }}({{ total_enabled }})</a></div>
                <div class="px-4 text-xs text-blue-500 font-semibold hover:underline"><a href="#">{{ $slots[ 'disabled' ] ? $slots[ 'disabled' ][0].text : __( 'Disabled' ) }} ({{ total_disabled }})</a></div>
            </div>
        </div>
        <div class="module-section flex-auto flex flex-wrap -mx-4">
            <div v-if="noModules" class="p-4 flex-auto flex">
                <div class="flex h-full flex-auto border-dashed border-2 border-box-edge bg-surface justify-center items-center">
                    <h2 class="font-bold text-xl text-primary">{{ noModuleMessage }}</h2>
                </div>
            </div>
            <div class="px-4 w-full md:w-1/2 lg:w-1/3 py-4" :key="moduleNamespace" v-for="(moduleObject,moduleNamespace) of modules">
                <div class="ns-modules rounded shadow overflow-hidden ns-box">
                    <div class="module-head h-40 p-2">
                        <h3 class="font-semibold text-lg">{{ moduleObject[ 'name' ] }}</h3>
                        <p class="text-xs flex justify-between">
                            <span>{{ moduleObject[ 'author' ] }}</span>
                            <strong>v{{ moduleObject[ 'version' ] }}</strong>
                        </p>
                        <p class="py-2 text-sm">{{ moduleObject[ 'description' ] }}</p>
                    </div>
                    <div class="ns-box-footer border-t p-2 flex justify-between">
                        <ns-button v-if="! moduleObject.enabled" @click="enableModule( moduleObject )" type="info">{{ __( 'Enable' ) }}</ns-button>
                        <ns-button v-if="moduleObject.enabled" @click="disableModule( moduleObject )" type="success">{{ __( 'Disable' ) }}</ns-button>
                        <div class="flex -mx-1">
                            <div class="px-1 flex -mx-1">
                                <div class="px-1 flex">
                                    <ns-button @click="download( moduleObject )" type="info">
                                        <i class="las la-archive"></i>
                                    </ns-button>
                                </div>
                                <div class="px-1 flex">
                                    <ns-button @click="removeModule( moduleObject )" type="error"><i class="las la-trash"></i></ns-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { nsHttpClient, nsSnackBar } from "../../bootstrap";
import { map } from "rxjs/operators";
import { __ } from '@/libraries/lang';

export default {
    name: 'ns-modules',
    props: [ 'url', 'upload' ],
    data() {
        return {
            modules: [],
            total_enabled : 0,
            total_disabled : 0,
        }
    },
    mounted() {
        this.loadModules().subscribe();
    },
    computed: {
        noModules() {
            return Object.values( this.modules ).length === 0;
        },
        noModuleMessage() {
            return this.$slots[ 'no-modules-message' ] ? this.$slots[ 'no-modules-message' ][0].text : __( `No module has been updated yet.` );
        }
    },
    methods: {
        __,
        
        download( module ) {
            document.location   =   '/dashboard/modules/download/' + module.namespace;
        },
        /**
         * @deprecated
         */
        performMigration: async ( module, migrations ) => {
            const syncRunMigration  =   async ( file, version ) => {
                return new Promise( ( resolve, reject ) => {
                    nsHttpClient.post( `/api/nexopos/v4/modules/${module.namespace}/migrate`, { file, version })
                        .subscribe( result => {
                            resolve( true );
                        }, error => {
                            return nsSnackBar.error( error.message, null, { duration: 4000 })
                                .subscribe();
                        })
                })
            }

            /**
             * if a migration is not provded
             * let's check from the module definition
             */
            migrations      =   migrations || module.migrations;

            if ( migrations ) {
                module.migrating    =   true;

                for( let version in migrations ) {
                    for( let index = 0; index < migrations[ version ].length ; index++ ) {
                        const file  =   migrations[ version ][ index ];
                        await syncRunMigration( file, version );
                    }
                }

                module.migrating     =   false;
                module.migrated      =   true;
            }
        },
        refreshModules() {
            this.loadModules().subscribe();
        },
        enableModule( object ) {
            const url   =   `${this.url}/${object.namespace}/enable`;
            nsHttpClient.put( url )
                .subscribe( async result => {
                    nsSnackBar.success( result.message ).subscribe();

                    this.loadModules().subscribe( result => {
                        document.location.reload();
                    }, ( error ) => {
                        nsSnackBar.error( error.message ).subscribe();
                    });
                }, ( error ) => {
                    nsSnackBar.error( error.message ).subscribe();
                });
        },
        disableModule( object ) {
            const url   =   `${this.url}/${object.namespace}/disable`;
            nsHttpClient.put( url )
                .subscribe( result => {
                    nsSnackBar.success( result.message ).subscribe();
                    this.loadModules().subscribe( result => {
                        document.location.reload();
                    }, ( error ) => {
                        nsSnackBar.error( error.message ).subscribe();
                    })
                }, ( error ) => {
                    nsSnackBar.error( error.message ).subscribe();
                });
        },
        loadModules() {
            return nsHttpClient.get( this.url )
                .pipe(
                    map( result => {                        
                        this.modules            =   result.modules;
                        this.total_enabled      =   result.total_enabled;
                        this.total_disabled     =   result.total_disabled;
                        return result;
                    })
                );
        },
        removeModule( module ) {
            if ( confirm( this.$slots[ 'confirm-delete-module' ] ? this.$slots[ 'confirm-delete-module' ][0].text : 'No text was provided for "confirm-delete-module" message.' ) ) {
                const url   =   `${this.url}/${module.namespace}/delete`;
                nsHttpClient.delete( url )
                    .subscribe( result => {
                        this.loadModules().subscribe( result => {
                            document.location.reload();
                        })
                    }, ( error ) => {
                        nsSnackBar.error( error.message, null, { duration: 5000 }).subscribe();
                    })
            }
        }
    }
}
</script>