<template>
    <div id="module-wrapper" class="flex-auto flex flex-col pb-4">
        <div class="flex flex-col lg:flex-row md:justify-between md:items-center">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center -mx-2">
                <span class="px-2">
                    <div class="ns-button mb-2">
                        <a @click="refreshModules()" class="items-center justify-center rounded cursor-pointer shadow flex px-3 py-1 ">
                            <i class="las la-sync"></i>
                            <span class="mx-2">{{ __( 'Refresh' ) }}</span>
                        </a>
                    </div>
                </span>
                <span class="px-2">
                    <div class="ns-button mb-2">
                        <a :href="upload" class="flex items-center justify-center rounded cursor-pointer shadow px-3 py-1">
                            <span>{{ __( 'Upload' ) }}</span>                        
                            <i class="las la-angle-right"></i>
                        </a>
                    </div>
                </span>
                <div class="px-2 w-auto">
                    <div class="input-group mb-2 shadow border-2 info rounded overflow-hidden">
                        <input ref="searchField" :placeholder="searchPlaceholder" v-model="searchText" type="text" class="w-full md:w-60 outline-none py-1 px-2">
                    </div>
                </div>
            </div>
            <div class="header-tabs flex -mx-4 flex-wrap">
                <div class="px-4 text-xs text-blue-500 font-semibold hover:underline"><a href="javascript:void(0)" @click="reloadModules( 'enabled' )">{{ __( 'Enabled' ) }}({{ total_enabled }})</a></div>
                <div class="px-4 text-xs text-blue-500 font-semibold hover:underline"><a href="javascript:void(0)" @click="reloadModules( 'disabled' )">{{ __( 'Disabled' ) }} ({{ total_disabled }})</a></div>
                <div class="px-4 text-xs text-blue-500 font-semibold hover:underline"><a href="javascript:void(0)" @click="reloadModules( 'invalid' )">{{ __( 'Invalid' ) }} ({{ total_invalid }})</a></div>
            </div>
        </div>
        <div class="module-section flex-auto flex flex-wrap -mx-4">
            <div v-if="noModules && searchText.length === 0" class="p-4 flex-auto flex">
                <div class="flex border-dashed border w-full border-primary h-32 flex-auto justify-center items-center">
                    <div class="text-primary">{{ noModuleMessage }}</div>
                </div>
            </div>
            <div v-if="noModules && searchText.length > 0" class="p-4 flex-auto flex">
                <div class="flex h-full flex-auto border-dashed border-2 border-box-edge bg-surface justify-center items-center">
                    <h2 class="font-bold text-xl text-primary text-center">{{ __( 'No modules matches your search term.' ) }}</h2>
                </div>
            </div>
            <div class="px-4 w-full md:w-1/2 lg:w-1/3 xl:1/4 py-4" :key="moduleNamespace" v-for="(moduleObject,moduleNamespace) of modules">
                <div class="ns-modules rounded shadow overflow-hidden ns-box">
                    <div class="module-head h-32 p-2">
                        <h3 class="font-semibold text-lg">{{ moduleObject[ 'name' ] }}</h3>
                        <div class="text-xs flex justify-between">
                            <div class="flex justify-between">
                                <span>{{ moduleObject[ 'author' ] }}</span>
                                <span class="text-error-tertiary mx-2" v-if="moduleObject[ 'psr-4-compliance' ] === false">
                                    &mdash; {{ __( 'not PSR-4 Compliant' ) }}
                                </span>
                            </div>
                            <strong>v{{ moduleObject[ 'version' ] }}</strong>
                        </div>
                        <p class="py-2 text-sm">
                            {{ truncateText( moduleObject.description, 20, '...' ) }}
                            <a class="text-xs text-info-tertiary hover:underline" @click="openPopupDetails( moduleObject )" v-if="countWords( moduleObject.description ) > 20" href="javascript:void(0)">[{{  __( 'Read More' ) }}]</a>
                        </p>
                    </div>
                    <div class="ns-box-footer border-t p-2 flex justify-between">
                        <ns-button :disabled="moduleObject.autoloaded || moduleObject[ 'psr-4-compliance' ] === false" v-if="! moduleObject.enabled" @click="enableModule( moduleObject )" type="info">{{ __( 'Enable' ) }}</ns-button>
                        <ns-button :disabled="moduleObject.autoloaded || moduleObject[ 'psr-4-compliance' ] === false" v-if="moduleObject.enabled" @click="disableModule( moduleObject )" type="success">{{ __( 'Disable' ) }}</ns-button>
                        <div class="flex -mx-1">
                            <div class="px-1 flex -mx-1">
                                <div class="px-1 flex">
                                    <ns-button :disabled="moduleObject.autoloaded" @click="download( moduleObject )" type="info">
                                        <i class="las la-archive"></i>
                                    </ns-button>
                                </div>
                                <div class="px-1 flex">
                                    <ns-button :disabled="moduleObject.autoloaded" @click="removeModule( moduleObject )" type="error"><i class="las la-trash"></i></ns-button>
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
import { __ } from '~/libraries/lang';
import { Popup } from '~/libraries/popup';
import { nsAlertPopup } from '~/components/components';

export default {
    name: 'ns-modules',
    props: [ 'url', 'upload' ],
    data() {
        return {
            rawModules: [],
            searchPlaceholder: __( 'Press "/" to search modules' ),
            total_enabled : 0,
            total_disabled : 0,
            total_invalid: 0,
            searchText: '',
            searchTimeOut: null
        }
    },
    mounted() {
        this.loadModules().subscribe();

        document.addEventListener('keypress', (event) => {
            if (event.key === '/') {
                if ( this.$refs.searchField !== null ) {
                    setTimeout( () => {
                        this.$refs.searchField.select();
                    }, 1 );
                }
            }
        });
    },
    watch: {
        // ...
    },
    computed: {
        noModules() {
            return Object.values( this.modules ).length === 0;
        },
        modules() {
            if ( this.searchText.length > 0 ) {
                const filteredModules   =   Object.values( this.rawModules ).filter( moduleData => {
                    const regEx     =   new RegExp( this.searchText, 'gi' );
                    const matches   =   moduleData.name.match( regEx );

                    if ( matches !== null ) {
                        return matches.length > 0;
                    }

                    return false;
                });

                const modules           =   new Object;

                for( let index = 0; index < filteredModules.length ; index++ ) {
                    modules[ filteredModules[index].namespace ]     =   filteredModules[index];
                }


                return modules;
            }

            return this.rawModules;
        },
        noModuleMessage() {
            return __( `There is nothing to display here.` );
        }
    },
    methods: {
        __,
        
        openPopupDetails( moduleDetails ) {
            Popup.show( nsAlertPopup, {
                title: __( '{module}' ).replace( '{module}', moduleDetails.name ),
                message: moduleDetails.description
            })
        },

        download( module ) {
            document.location   =   '/dashboard/modules/download/' + module.namespace;
        },
        truncateText(text, maxLength, replacement = '...' ) {
            let words = text.split(' ');

            if (words.length > maxLength) {
                words = words.slice(0, maxLength);
                words.push( replacement );
            }
            return words.join(' ');
        },

        countWords( text ) {
            return text.split( ' ' ).length;
        },

        reloadModules( segment ) {
            return this.loadModules( this.url + '/' + segment ).subscribe();
        },

        refreshModules() {
            this.loadModules().subscribe();
        },
        enableModule( object ) {
            const url   =   `${this.url}/${object.namespace}/enable`;
            nsHttpClient.put( url )
                .subscribe({
                    next: async result => {
                        nsSnackBar.success( result.message ).subscribe();

                        this.loadModules().subscribe({
                            next: result => {
                                document.location.reload();
                            },
                            error: ( error ) => {
                                nsSnackBar.error( error.message ).subscribe();
                            }
                        });
                    },
                    error: ( error ) => {
                        nsSnackBar.error( error.message ).subscribe();
                    }
                });
        },
        disableModule( object ) {
            const url   =   `${this.url}/${object.namespace}/disable`;
            nsHttpClient.put( url )
                .subscribe({
                    next: result => {
                        nsSnackBar.success( result.message ).subscribe();
                        this.loadModules().subscribe({
                            next: result => {
                                document.location.reload();
                            },
                            error: ( error ) => {
                                nsSnackBar.error( error.message ).subscribe();
                            }
                        })
                    },
                    error: ( error ) => {
                        nsSnackBar.error( error.message ).subscribe();
                    }
                });
        },
        loadModules( url ) {
            return nsHttpClient.get( url || this.url )
                .pipe(
                    map( result => {                        
                        this.rawModules         =   result.modules;
                        this.total_enabled      =   result.total_enabled;
                        this.total_disabled     =   result.total_disabled;
                        this.total_invalid      =   result.total_invalid;
                        return result;
                    })
                );
        },
        removeModule( module ) {
            if ( confirm( __( 'Would you like to delete "{module}"? All data created by the module might also be deleted.' ).replace( '{module}', module.name ) ) ) {
                const url   =   `${this.url}/${module.namespace}/delete`;
                nsHttpClient.delete( url )
                    .subscribe({
                        next: result => {
                            this.loadModules().subscribe({
                                next: result => {
                                    document.location.reload();
                                }
                            })
                        },
                        error: ( error ) => {
                            nsSnackBar.error( error.message, null, { duration: 5000 }).subscribe();
                        }
                    })
            }
        }
    }
}
</script>