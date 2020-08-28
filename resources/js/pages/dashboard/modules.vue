<template>
    <div id="module-wrapper" class="flex-auto flex flex-col pb-4">
        <div class="flex justify-between items-center">
            <div class="flex justify-between items-center -mx-2">
                <span class="px-2">
                    <a @click="loadModules()" class="rounded-full text-gray-600 bg-white shadow flex items-center justify-center px-3 py-1 h-10 w-10 hover:bg-blue-400 hover:text-white"><i class="las la-sync"></i></a>
                </span>
                <span class="px-2">
                    <a :href="upload" class="rounded-lg text-gray-600 bg-white shadow px-3 py-1 hover:bg-blue-400 hover:text-white">Upload<i class="las la-angle-right"></i></a>
                </span>
            </div>
            <div class="header-tabs flex -mx-4 flex-wrap">
                <div class="px-4 text-xs text-blue-500 font-semibold hover:underline"><a href="#">{{ $slots[ 'enabled' ] ? $slots[ 'enabled' ][0].text : 'Enabled' }}({{ total_enabled }})</a></div>
                <div class="px-4 text-xs text-blue-500 font-semibold hover:underline"><a href="#">{{ $slots[ 'disabled' ] ? $slots[ 'disabled' ][0].text : 'Disabled' }} ({{ total_disabled }})</a></div>
            </div>
        </div>
        <div class="module-section flex-auto flex flex-wrap py-4 -my-4 -mx-4">
            <div v-if="noModules" class="p-4 flex-auto flex">
                <div class="flex h-full flex-auto border-dashed border-2 border-gray-600 bg-white justify-center items-center">
                    <h2 class="font-bold text-xl text-gray-700">{{ noModuleMessage }}</h2>
                </div>
            </div>
            <div class="px-4 w-full md:w-1/2 lg:w-1/3 py-4" :key="moduleNamespace" v-for="(moduleObject,moduleNamespace) of modules">
                <div class="rounded shadow overflow-hidden">
                    <div class="module-head h-40 p-2 bg-white">
                        <h3 class="font-semibold text-lg text-gray-700">{{ moduleObject[ 'name' ] }}</h3>
                        <p class="text-gray-600 text-xs flex justify-between">
                            <span>{{ moduleObject[ 'author' ] }}</span>
                            <strong>v{{ moduleObject[ 'version' ] }}</strong>
                        </p>
                        <p class="py-2 text-gray-700 text-sm">{{ moduleObject[ 'description' ] }}</p>
                    </div>
                    <div class="footer bg-gray-200 p-2 flex justify-between">
                        <ns-button v-if="! moduleObject.enabled" @click="enableModule( moduleObject )" type="info">Enable</ns-button>
                        <ns-button v-if="moduleObject.enabled" @click="disableModule( moduleObject )" type="success">Disable</ns-button>
                        <ns-button @click="removeModule( moduleObject )" type="danger"><i class="las la-trash"></i></ns-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { nsHttpClient, nsSnackBar } from "../../bootstrap";
import { map } from "rxjs/operators";

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
            return this.$slots[ 'no-modules-message' ] ? this.$slots[ 'no-modules-message' ][0].text : 'No message provided for "no-module-message"';
        }
    },
    methods: {
        enableModule( object ) {
            const url   =   `${this.url}/${object.namespace}/enable`;
            nsHttpClient.put( url )
                .subscribe( result => {
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