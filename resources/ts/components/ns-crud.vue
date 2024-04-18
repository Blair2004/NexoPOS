<template>
    <div id="crud-table" class="w-full rounded-lg" :class="mode !== 'light' ? 'shadow mb-8': ''">
        <div id="crud-table-header" class="p-2 border-b flex flex-col md:flex-row justify-between flex-wrap" v-if="mode !== 'light'">
            <div id="crud-search-box" class="w-full md:w-auto -mx-2 mb-2 md:mb-0 flex">
                <div v-if="createUrl" class="px-2 flex items-center justify-center">
                    <a :href="createUrl || '#'" class="rounded-full ns-crud-button text-sm h-10 flex items-center justify-center cursor-pointer px-3 outline-none border"><i class="las la-plus"></i></a>
                </div>
                <div class="px-2">
                    <div class="rounded-full p-1 ns-crud-input flex">
                        <input @keypress.enter="search()" v-model="searchInput" type="text" class="w-36 md:w-auto bg-transparent outline-none px-2">
                        <button @click="search()" class="rounded-full w-8 h-8 outline-none ns-crud-input-button"><i class="las la-search"></i></button>
                        <button v-if="searchQuery" @click="cancelSearch()" class="ml-1 rounded-full w-8 h-8 bg-error-secondary outline-none hover:bg-error-tertiary"><i class="las la-times text-white"></i></button>
                    </div>
                </div>
                <div class="px-2 flex items-center justify-center">
                    <button @click="refresh()" class="rounded-full
                    text-sm
                    h-10
                    px-3
                    outline-none
                    border
                    ns-crud-button
                    "><i :class="isRefreshing ? 'animate-spin' : ''" class="las la-sync"></i> </button>
                </div>
                <div class="px-2 flex items-center" v-if="showQueryFilters">
                    <button @click="openQueryFilter()" :class="withFilters ? 'table-filters-enabled' : 'table-filters-disabled'" class="ns-crud-button border rounded-full text-sm h-10 px-3 outline-none ">
                        <i v-if="! withFilters" class="las la-filter"></i>
                        <i v-if="withFilters" class="las la-check"></i>
                        <span class="ml-1" v-if="! withFilters">{{ __( 'Filters' ) }}</span>
                        <span class="ml-1" v-if="withFilters">{{ __( 'Has Filters' ) }}</span>
                    </button>
                </div>
                <div id="custom-buttons" v-if="headerButtonsComponents.length > 0">
                    <component @refresh="refresh()" :result="result" :is="component" :key="index" v-for="(component, index) of headerButtonsComponents"/>
                </div>
            </div>
            <div id="crud-buttons" class="-mx-1 flex flex-wrap w-full md:w-auto">
                <div class="px-1 flex items-center" v-if="selectedEntries.length > 0">
                    <button @click="clearSelectedEntries()" class="flex justify-center items-center rounded-full text-sm h-10 px-3 outline-none ns-crud-button border">
                        <i class="lar la-check-square"></i> {{ __( '{entries} entries selected' ).replace( '{entries}', selectedEntries.length ) }}
                    </button>
                </div>
                <div class="px-1 flex items-center">
                    <button @click="downloadContent()" class="flex justify-center items-center rounded-full text-sm h-10 px-3 ns-crud-button border outline-none"><i class="las la-download"></i> {{ __( 'Download' ) }}</button>
                </div>
            </div>
        </div>
        <div class="flex p-2">
            <div class="overflow-x-auto flex-auto">
                <table class="table ns-table w-full" v-if="Object.values( columns ).length > 0">
                    <thead>
                        <tr>
                            <th v-if="showCheckboxes" class="text-center px-2 border w-16 py-2">
                                <ns-checkbox :checked="globallyChecked" @change="handleGlobalChange( $event )"></ns-checkbox>
                            </th>
                            <th v-if="prependOptions && showOptions" class="text-left px-2 py-2 w-16 border"></th>
                            <th :key="identifier" @click="sort( identifier )" v-for="(column, identifier) of columns" :style="{ 
                                'width' : column.width || 'auto', 
                                'max-width': column.maxWidth || 'auto', 
                                'min-width': column.minWidth || 'auto' 
                            }" class="cursor-pointer justify-betweenw-40 border text-left px-2 py-2">
                                <div class="w-full flex justify-between items-center">
                                    <span class="flex">{{ column.label }}</span>
                                    <span class="h-6 w-6 flex justify-center items-center">
                                        <i v-if="column.$direction === 'desc'" class="las la-sort-amount-up"></i>
                                        <i v-if="column.$direction === 'asc'" class="las la-sort-amount-down"></i>
                                    </span>
                                </div>
                            </th>
                            <th v-if="!prependOptions && showOptions" class="text-left px-2 py-2 w-16 border"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="result.data !== undefined && result.data.length > 0">
                            <ns-table-row
                                :key="index"
                                @updated="refreshRow( $event )"
                                v-for="(row,index) of result.data"
                                :columns="columns"
                                :prependOptions="prependOptions"
                                :showOptions="showOptions"
                                :showCheckboxes="showCheckboxes"
                                :row="row"
                                @reload="refresh()"
                                @toggled="handleShowOptions( $event )"></ns-table-row>
                        </template>
                        <tr v-if="! result || result.data.length === 0">
                            <td :colspan="Object.values( columns ).length + 2" class="text-center border py-3">{{ __( 'There is nothing to display...' ) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="p-2 flex border-t flex-col md:flex-row justify-between footer">
            <div v-if="bulkActions.length > 0" id="grouped-actions" class="mb-2 md:mb-0 flex justify-between rounded-full ns-crud-input p-1">
                <select class="outline-none bg-transparent" v-model="bulkAction" id="grouped-actions">
                    <option class="bg-input-disabled" selected value=""><slot name="bulk-label">{{ __( 'Bulk Actions' ) }}</slot></option>
                    <option class="bg-input-disabled" :key="index" v-for="(action, index) of bulkActions" :value="action.identifier">{{ action.label }}</option>
                </select>
                <button @click="bulkDo()" class="ns-crud-input-button h-8 px-3 outline-none rounded-full flex items-center justify-center"><slot name="bulk-go">{{ __( 'Apply' ) }}</slot></button>
            </div>
            <div class="flex">
                <div class="items-center flex text-primary mx-4">{{ resultInfo }}</div>
                <div id="pagination" class="flex items-center -mx-1">
                    <template v-if="result.current_page">
                        <a href="javascript:void(0)" @click="page=result.first_page;refresh()" class="mx-1 flex items-center justify-center h-8 w-8 rounded-full ns-crud-button border shadow">
                            <i class="las la-angle-double-left"></i>
                        </a>
                        <template v-for="(_paginationPage, index) of pagination">
                            <a :key="index" v-if="page !== '...'" :class="page == _paginationPage ? 'bg-info-tertiary border-transparent text-white' : ''" @click="page=_paginationPage;refresh()" href="javascript:void(0)" class="mx-1 flex items-center justify-center h-8 w-8 rounded-full ns-crud-button border">{{ _paginationPage }}</a>
                            <a :key="index" v-if="page === '...'" href="javascript:void(0)" class="mx-1 flex items-center justify-center h-8 w-8 rounded-full ns-crud-button border">...</a>
                        </template>
                        <a href="javascript:void(0)" @click="page=result.last_page;refresh()" class="mx-1 flex items-center justify-center h-8 w-8 rounded-full ns-crud-button border shadow">
                            <i class="las la-angle-double-right"></i>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { nsSnackBar } from '~/bootstrap';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import { Popup } from '~/libraries/popup';
import { __ } from '~/libraries/lang';
import { HttpStatusResponse } from '~/interfaces/http-status-response';
import { HttpCrudResponse } from '~/interfaces/http-crud-response';
import nsOrdersFilterPopupVue from '~/popups/ns-orders-filter-popup.vue';
import { defineAsyncComponent } from 'vue';

declare const nsCrudHandler;
declare const nsExtraComponents;

export default {
    data: () => {
        return {
            prependOptions: false,
            showOptions: true,
            showCheckboxes: true,
            isRefreshing: false,
            sortColumn: '',
            searchInput: '',
            queryFiltersString: '',
            searchQuery: '',
            page: 1,
            bulkAction: '',
            bulkActions: [],
            queryFilters:[],
            headerButtons: [],
            withFilters: false,
            columns: [],
            selectedEntries:[],
            globallyChecked: false,
            result: {
                current_page: null,
                data: [],
                first_page_url: null,
                from: null,
                last_page: null,
                last_page_url: null,
                next_page_url: null,
                path: null,
                per_page: null,
                prev_page_url: null,
                to: null,
                total: null,
            }
        }
    },
    name: 'ns-crud',
    mounted() {
        if ( this.identifier !== undefined  ) {
            nsCrudHandler.defineInstance( this.identifier, this );
        }

        this.loadConfig();
    },
    props: [ 'src', 'createUrl', 'mode', 'identifier', 'queryParams', 'popup' ],
    computed: {
        /**
         * helps to get parsed
         * src link. Useful to enable sort
         * pagination, total items per pages
         */
        getParsedSrc() {
            return `${this.src}?${this.sortColumn}${this.searchQuery}${this.queryFiltersString}${this.queryPage}${this.getQueryParams() ? '&' + this.getQueryParams() : ''}`
        },

        showQueryFilters() {
            return this.queryFilters.length > 0;
        },

        getSelectedAction() {
            const action    =   this.bulkActions.filter( action => {
                return action.identifier === this.bulkAction;
            });
            return action.length > 0 ? action[0] : false;
        },

        pagination() {
            if ( this.result ) {
                return this.pageNumbers( this.result.last_page, this.result.current_page );
            }
            return [];
        },

        queryPage() {
            if ( this.result ) {
                return `&page=${this.page}`
            }
            return '';
        },
        resultInfo() {
            return __( 'displaying {perPage} on {items} items' )
                .replace( '{perPage}', this.result.per_page || 0 )
                .replace( '{items}', this.result.total || 0 )
        },
        headerButtonsComponents() {
            return this.headerButtons.map( buttonComponent => {
                return defineAsyncComponent( () => {
                    return new Promise( ( resolve ) => {
                        resolve( nsExtraComponents[ buttonComponent ] );
                    })
                })
            });
        }
    },
    methods: {
        __,
        getQueryParams() {
            if ( this.queryParams ) {
                return ( Object.keys( this.queryParams )
                    .map(key => `${key}=${this.queryParams[key]}`)
                    .join('&') );
            }
            return '';
        },

        pageNumbers(count, current) {
            var shownPages = 3;
            var result = [];

            if ( current - 3 > 1 ) {
                result.push( 1, '...' );
            }

            for( let i = 1; i <= count; i++ ) {
                if ( current + 3 > i && current - 3 < i ) {
                    result.push(i);
                }
            }

            if ( current + 3 < count ) {
                result.push( '...', count );
            }

            return result.filter( f => f > 0 || typeof f === 'string' );
        },

        downloadContent() {
            nsHttpClient.post( `${this.src}/export?${this.getParsedSrc}`, { entries : this.selectedEntries.map( e => e.$id ) })
                .subscribe( (result: any) => {
                    setTimeout( () => document.location   =   result.url, 300 );
                    nsSnackBar
                        .success( __( 'The document has been generated.' ) )
                        .subscribe()
                }, error => {
                    nsSnackBar
                        .error( error.message || __( 'Unexpected error occurred.' ) )
                        .subscribe();
                })
        },

        clearSelectedEntries() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Clear Selected Entries ?' ),
                message: __( 'Would you like to clear all selected entries ?' ),
                onAction: ( action ) => {
                    if ( action ) {
                        this.selectedEntries    =   [];
                        this.handleGlobalChange( false );
                    }
                }
            });
        },

        /**
         * Will select a row and add it to a virtual storage
         * that will ensure that while browsing to pages, these
         * entries remains selected.
         * @param row actual row
         */
        refreshRow( row ) {
            console.log({ row });
            if ( row.$checked === true ) {
                const result    =   this.selectedEntries.filter( e => e.$id === row.$id );

                if ( result.length  === 0 ) {
                    this.selectedEntries.push( row );
                }
            } else {
                const result    =   this.selectedEntries.filter( e => e.$id === row.$id );

                if ( result.length > 0 ) {
                    const index     =   this.selectedEntries.indexOf( result[0] );
                    this.selectedEntries.splice( index, 1 );
                }
            }
        },

        handleShowOptions( e ) {
            this.result.data.forEach( row => {
                if ( row.$id !== e.$id ) {
                    row.$toggled    =   false;
                }
            });
        },
        handleGlobalChange( event ) {
            this.globallyChecked    =   event;
            this.result.data.forEach( r => {
                r.$checked = event;
                this.refreshRow( r );
            });
        },
        loadConfig() {
            const request   =   nsHttpClient.get( `${this.src}/config?${this.getQueryParams()}` );
            request.subscribe( (f:any) => {
                this.columns        =   f.columns;
                this.bulkActions    =   f.bulkActions;
                this.queryFilters   =   f.queryFilters;
                this.prependOptions =   f.prependOptions;
                this.showOptions    =   f.showOptions;
                this.showCheckboxes =   f.showCheckboxes;
                this.headerButtons  =   f.headerButtons || [];
                this.refresh();
            }, ( error ) => {
                nsSnackBar.error( error.message, 'OK', { duration: false }).subscribe();
            });
        },
        cancelSearch() {
            this.searchInput    =   '';
            this.search();
        },
        search() {
            if ( this.searchInput ) {
                this.searchQuery    =   `&search=${this.searchInput}`;
            } else {
                this.searchQuery    =   '';
            }

            this.page   =   1;

            this.refresh();
        },
        sort( identifier ) {
            if ( this.columns[ identifier ].$sort === false ) {
                return nsSnackBar.error( __( 'Sorting is explicitely disabled on this column' ) ).subscribe();
            }

            for ( let key in this.columns ) {
                if ( key !== identifier ) {
                    this.columns[ key ].$sorted     =   false;
                    this.columns[ key ].$direction  =   '';
                }
            }

            this.columns[ identifier ].$sorted      =   true;

            switch( this.columns[ identifier ].$direction ) {
                case 'asc':
                    this.columns[ identifier ].$direction   =   'desc';
                break;
                case 'desc':
                    this.columns[ identifier ].$direction   =   '';
                break;
                case '':
                default:
                    this.columns[ identifier ].$direction   =   'asc';
                break;
            }

            if ( [ 'asc', 'desc' ].includes( this.columns[ identifier ].$direction ) ) {
                this.sortColumn     =   `active=${identifier}&direction=${this.columns[ identifier ].$direction}`;
            } else {
                this.sortColumn     =   '';
            }

            this.$emit( 'sort', this.columns[ identifier ] );
            this.refresh();
        },
        bulkDo() {
            if ( this.bulkAction ) {
                if ( this.selectedEntries.length > 0 ) {
                    if ( confirm( this.getSelectedAction.confirm || __( 'Would you like to perform the selected bulk action on the selected entries ?' ) ) ) {
                        return nsHttpClient.post( `${this.src}/bulk-actions`, {
                            action: this.bulkAction,
                            entries: this.selectedEntries.map( r => r.$id )
                        }).subscribe({
                            next: (result: HttpStatusResponse ) => {
                                nsSnackBar.info( result.message ).subscribe();
                                this.selectedEntries    =   [];
                                this.refresh();
                            },
                            error: ( error ) => {
                                nsSnackBar.error( error.message )
                                    .subscribe();
                            }
                        })
                    }
                } else {
                    return nsSnackBar.error( __( 'No selection has been made.' ) )
                        .subscribe();
                }
            } else {
                return nsSnackBar.error( __( 'No action has been selected.' ) )
                    .subscribe();
            }

        },

        async openQueryFilter() {
            try {
                const result    =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsOrdersFilterPopupVue, { resolve, reject, queryFilters: this.queryFilters });
                });

                this.withFilters                =   false;
                this.queryFiltersString         =   '';

                /**
                 * in case there is a change
                 * on the query filters.
                 */
                if ( result !== null ) {
                    this.withFilters            =   true;
                    this.queryFiltersString     =   '&queryFilters=' + encodeURIComponent( JSON.stringify(result) );
                }

                this.refresh();
            } catch( exception ) {
                // ...
            }
        },

        refresh() {
            this.globallyChecked    =   false;
            this.isRefreshing       =   true;
            const request           =   nsHttpClient.get( `${this.getParsedSrc}` );
            request.subscribe( (f:HttpCrudResponse) => {
                /**
                 * if the entries were already
                 * checked, we'll make sure to restore the checked status.
                 */
                f.data          =   f.data.map( entry => {
                    const selected  =   this.selectedEntries.filter( e => e.$id === entry.$id ).length > 0;

                    if( selected ) {
                        entry.$checked  =   true;
                    }

                    return entry;
                });

                this.isRefreshing   =   false;
                this.result     =   f;
                this.page       =   f.current_page;
            }, ( error ) => {
                this.isRefreshing   =   false;
                nsSnackBar.error( error.message ).subscribe();
            });
        }
    },
}
</script>
