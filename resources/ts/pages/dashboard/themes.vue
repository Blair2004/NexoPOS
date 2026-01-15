<template>
    <div id="theme-wrapper" class="flex-auto flex flex-col pb-4">
        <div class="flex flex-col lg:flex-row md:justify-between md:items-center">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center -mx-2">
                <span class="px-2">
                    <div class="ns-button mb-2">
                        <a @click="refreshThemes()" class="items-center justify-center rounded cursor-pointer shadow flex px-3 py-1 ">
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
                        <input ref="searchField" :placeholder="searchPlaceholder" v-model="searchText" type="text" class="w-full md:w-60 outline-hidden py-1 px-2">
                    </div>
                </div>
            </div>
            <div class="header-tabs flex -mx-4 flex-wrap">
                <div class="px-4 text-xs text-blue-500 font-semibold hover:underline"><a href="javascript:void(0)" @click="reloadThemes( 'enabled' )">{{ __( 'Enabled' ) }}({{ total_enabled }})</a></div>
                <div class="px-4 text-xs text-blue-500 font-semibold hover:underline"><a href="javascript:void(0)" @click="reloadThemes( 'disabled' )">{{ __( 'Disabled' ) }} ({{ total_disabled }})</a></div>
            </div>
        </div>
        <div class="theme-section flex-auto flex flex-wrap -mx-4">
            <div v-if="noThemes && searchText.length === 0" class="p-4 flex-auto flex">
                <div class="flex border-dashed border w-full border-secondary h-32 flex-auto justify-center items-center">
                    <div class="text-fontcolor">{{ noThemeMessage }}</div>
                </div>
            </div>
            <div v-if="noThemes && searchText.length > 0" class="p-4 flex-auto flex">
                <div class="flex h-full flex-auto border-dashed border-2 border-box-edge bg-surface justify-center items-center">
                    <h2 class="font-bold text-xl text-fontcolor text-center">{{ __( 'No themes matches your search term.' ) }}</h2>
                </div>
            </div>
            <div class="px-4 w-full md:w-1/2 lg:w-1/3 xl:1/4 py-4" :key="themeNamespace" v-for="(themeObject,themeNamespace) of themes">
                <div class="ns-themes rounded shadow overflow-hidden ns-box">
                    <div v-if="themeObject['preview-image']" class="theme-preview h-40 bg-cover bg-center" :style="{ backgroundImage: `url(${getPreviewImage(themeObject)})` }"></div>
                    <div v-else class="theme-preview h-40 bg-surface flex items-center justify-center">
                        <i class="las la-image text-6xl text-secondary"></i>
                    </div>
                    <div class="theme-head p-2">
                        <h3 class="font-semibold text-lg">{{ themeObject[ 'name' ] }}</h3>
                        <div class="text-xs flex justify-between">
                            <div class="flex justify-between">
                                <span>{{ themeObject[ 'author' ] }}</span>
                            </div>
                            <strong>v{{ themeObject[ 'version' ] }}</strong>
                        </div>
                        <p class="py-2 text-sm" v-if="typeof themeObject.description === 'string'">
                            {{ truncateText( themeObject.description, 20, '...' ) }}
                        </p>
                        <template v-if="typeof themeObject.description === 'object'">
                            <p class="py-2 text-sm">
                                {{ truncateText( themeObject.description[ currentLocale ] || themeObject.description[ 'en' ], 20, '...' ) }}
                            </p>
                        </template>
                    </div>
                    <div class="ns-box-footer border-t p-2 flex justify-between">
                        <ns-button v-if="! themeObject.enabled" @click="enableTheme( themeObject )" type="default">{{ __( 'Enable' ) }}</ns-button>
                        <ns-button v-if="themeObject.enabled" @click="disableTheme( themeObject )" type="warning">{{ __( 'Disable' ) }}</ns-button>
                        <div class="flex -mx-1">
                            <div class="px-1 flex -mx-1">
                                <div class="px-1 flex">
                                    <ns-button @click="download( themeObject )" type="info">
                                        <i class="las la-archive"></i>
                                    </ns-button>
                                </div>
                                <div class="px-1 flex">
                                    <ns-button :disabled="themeObject.enabled" @click="removeTheme( themeObject )" type="error"><i class="las la-trash"></i></ns-button>
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
import { __ } from '~/libraries/lang';
import { Popup } from '~/libraries/popup';
import nsConfirmPopup from '~/popups/ns-pos-confirm-popup.vue';

export default {
    name: 'ns-themes',
    props: [ 'url', 'upload' ],
    data() {
        return {
            themes: {},
            total_enabled: 0,
            total_disabled: 0,
            total_invalid: 0,
            searchText: '',
            searchPlaceholder: __( 'Search Themes...' ),
            noThemeMessage: __( 'No theme installed yet.' ),
            currentLocale: ns.settingsReference?.[ 'locale' ] || 'en',
        }
    },
    mounted() {
        this.loadThemes();
    },
    computed: {
        noThemes() {
            return Object.keys( this.themes ).length === 0;
        }
    },
    watch: {
        searchText( value ) {
            if ( value.length > 0 ) {
                this.themes = Object.keys( this.themes )
                    .filter( namespace => {
                        const theme = this.themes[ namespace ];
                        return theme.name.toLowerCase().includes( value.toLowerCase() ) ||
                               theme.namespace.toLowerCase().includes( value.toLowerCase() );
                    })
                    .reduce( (result, key) => {
                        result[ key ] = this.themes[ key ];
                        return result;
                    }, {} );
            } else {
                this.loadThemes();
            }
        }
    },
    methods: {
        __,
        getPreviewImage( theme ) {
            // The preview-image path is absolute, we need to make it relative to public
            if ( theme['preview-image'] ) {
                const path = theme['preview-image'];
                // Extract the relative path from themes directory
                const themesIndex = path.indexOf('themes/');
                if (themesIndex !== -1) {
                    return '/' + path.substring(themesIndex);
                }
            }
            return '';
        },
        truncateText( text, limit, ellipsis = '...' ) {
            if ( ! text ) return '';
            const words = text.split(' ');
            if ( words.length <= limit ) return text;
            return words.slice( 0, limit ).join(' ') + ellipsis;
        },
        refreshThemes() {
            location.reload();
        },
        reloadThemes( argument = '' ) {
            nsHttpClient.get( `${this.url}/${argument}` )
                .subscribe( result => {
                    this.themes = result.themes;
                    this.total_enabled = result.total_enabled;
                    this.total_disabled = result.total_disabled;
                    this.total_invalid = result.total_invalid;
                });
        },
        loadThemes() {
            nsHttpClient.get( this.url )
                .subscribe( result => {
                    this.themes = result.themes;
                    this.total_enabled = result.total_enabled;
                    this.total_disabled = result.total_disabled;
                    this.total_invalid = result.total_invalid;
                });
        },
        enableTheme( theme ) {
            if ( this.total_enabled > 0 ) {
                Popup.show( nsConfirmPopup, {
                    title: __( 'Confirm Action' ),
                    message: __( 'Only one theme can be enabled at a time. Enabling this theme will disable the currently active theme. Do you want to continue?' ),
                    onAction: (confirmed) => {
                        if ( confirmed ) {
                            this.proceedEnableTheme( theme );
                        }
                    }
                });
            } else {
                this.proceedEnableTheme( theme );
            }
        },
        proceedEnableTheme( theme ) {
            nsHttpClient.put( `${this.url}/${theme.namespace}/enable` )
                .subscribe( result => {
                    nsSnackBar.success( result.message ).subscribe();
                    this.loadThemes();
                }, error => {
                    nsSnackBar.error( error.message || __( 'An error occurred while enabling the theme.' ) ).subscribe();
                });
        },
        disableTheme( theme ) {
            Popup.show( nsConfirmPopup, {
                title: __( 'Confirm Action' ),
                message: __( 'Do you want to disable this theme?' ),
                onAction: (confirmed) => {
                    if ( confirmed ) {
                        nsHttpClient.put( `${this.url}/${theme.namespace}/disable` )
                            .subscribe( result => {
                                nsSnackBar.success( result.message ).subscribe();
                                this.loadThemes();
                            }, error => {
                                nsSnackBar.error( error.message || __( 'An error occurred while disabling the theme.' ) ).subscribe();
                            });
                    }
                }
            });
        },
        removeTheme( theme ) {
            Popup.show( nsConfirmPopup, {
                title: __( 'Confirm Action' ),
                message: __( 'Do you want to delete this theme? This action cannot be undone.' ),
                onAction: (confirmed) => {
                    if ( confirmed ) {
                        nsHttpClient.delete( `${this.url}/${theme.namespace}/delete` )
                            .subscribe( result => {
                                nsSnackBar.success( result.message ).subscribe();
                                this.loadThemes();
                            }, error => {
                                nsSnackBar.error( error.message || __( 'An error occurred while deleting the theme.' ) ).subscribe();
                            });
                    }
                }
            });
        },
        download( theme ) {
            document.location = `/dashboard/themes/download/${theme.namespace}`;
        }
    }
}
</script>
