import Vue from 'vue';
import { HttpCrudResponse } from '../interfaces/http-crud-response';
import { HttpStatusResponse } from '../interfaces/http-status-response';
import { nsHttpClient, nsSnackBar }   from './../bootstrap';

const nsCrud    =   Vue.component( 'ns-crud', {
    data: () => {
        return {
            sortColumn: '',
            searchInput: '',
            searchQuery: '',
            page: 1,
            bulkAction: '',
            bulkActions: [],
            columns: [],
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
    mounted() {
        this.loadConfig();
    },
    props: [ 'src', 'create-url' ],
    computed: {
        /**
         * helps to get parsed
         * src link. Useful to enable sort
         * pagination, total items per pages
         */
        getParsedSrc() {
            return `${this.src}?${this.sortColumn}${this.searchQuery}${this.queryPage}`
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
        }
    },
    methods: {
        pageNumbers(count, current) {
            var shownPages = 3;
            var result = [];
            if (current > count - shownPages) {
                result.push(count - 2, count - 1, count);
            } else {
                result.push(current, current + 1, current + 2, '...', count);
            }
            return result.filter( f => f > 0 || typeof f === 'string' );
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
            this.result.data.forEach( r => r.$checked = event );
        },
        loadConfig() {
            const request   =   nsHttpClient.get( `${this.src}/config` );
            request.subscribe( (f:any) => {
                this.columns        =   f.columns;
                this.bulkActions    =   f.bulkActions;
                this.refresh();
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

            this.refresh();
        },
        sort( identifier ) {

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
                if ( this.result.data.filter( row => row.$checked ).length > 0 ) {
                    console.log( this.getSelectedAction );
                    if ( confirm( this.getSelectedAction.confirm || this.$slots[ 'error-bulk-confirmation' ] || 'No bulk confirmation message provided on the CRUD class.' ) ) {
                        return nsHttpClient.post( `${this.src}/bulk-actions`, {
                            action: this.bulkAction,
                            entries: this.result.data.filter( row => row.$checked ).map( r => r.$id )
                        }).subscribe( (result: HttpStatusResponse ) => {
                            nsSnackBar.info( result.message ).subscribe();
                            this.refresh();
                        }, ( error ) => {
                            nsSnackBar.error( error.message )
                                .subscribe();
                        })
                    }
                } else {
                    return nsSnackBar.error( this.$slots[ 'error-no-selection' ] ? this.$slots[ 'error-no-selection' ][0].text : 'No error provided when there is no selection selected (error-no-selection).' )
                        .subscribe();
                }
            } else {
                return nsSnackBar.error( this.$slots[ 'error-no-action' ] ? this.$slots[ 'error-no-action' ][0].text : 'No error provided when there is no action selected (error-no-action).' )
                    .subscribe();
            }

        },
        refresh() {
            const request   =   nsHttpClient.get( `${this.getParsedSrc}` );
            request.subscribe( (f:HttpCrudResponse) => {
                this.result     =   f;
                this.page       =   f.current_page;
            });
        }
    },
    template: `
    <div id="crud-table" class="w-full shadow rounded-lg bg-white mb-8">
        <div id="crud-table-header" class="p-2 border-b border-gray-200 flex justify-between flex-wrap">
            <div id="crud-search-box" class="w-full md:w-auto -mx-2 flex">
                <div class="px-2 flex items-center justify-center">
                    <a :href="createUrl || '#'" class="rounded-full hover:border-blue-400 hover:text-white hover:bg-blue-400 text-sm h-10 flex items-center justify-center cursor-pointer bg-white px-3 outline-none text-gray-800 border border-gray-400"><i class="las la-plus"></i></a>
                </div>
                <div class="px-2">
                    <div class="rounded-full p-1 bg-gray-200 flex">
                        <input v-model="searchInput" type="text" class="bg-transparent outline-none px-2">
                        <button @click="search()" class="rounded-full w-8 h-8 bg-white outline-none hover:bg-blue-400 hover:text-white"><i class="las la-search"></i></button>
                        <button v-if="searchQuery" @click="cancelSearch()" class="ml-1 rounded-full w-8 h-8 bg-red-400 text-white outline-none hover:bg-red-500 hover:text-white"><i class="las la-times"></i></button>
                    </div>
                </div>
                <div class="px-2 flex">
                    <button @click="refresh()" class="rounded-full hover:border-blue-400 hover:text-white hover:bg-blue-400 text-sm h-10 bg-white px-3 outline-none text-gray-800 border border-gray-400"><i class="las la-sync"></i> </button>
                </div>
            </div>
            <div id="crud-buttons" class="-mx-1 flex flex-wrap w-full md:w-auto">
                <div class="px-1 flex">
                    <button class="flex justify-center items-center rounded-full text-sm h-10 w-10 bg-teal-400 outline-none text-white font-semibold"><i class="las la-download"></i></button>
                </div>
                <div class="px-1 flex">
                    <button class="flex justify-center items-center rounded-full text-sm h-10 w-10 bg-green-400 outline-none text-white font-semibold"><i class="las la-upload"></i></button>
                </div>
                <div class="px-1 flex">
                    <button class="flex justify-center items-center rounded-full text-sm h-10 w-10 hover:border-blue-400 hover:text-white hover:bg-blue-400 outline-none border-gray-400 border text-gray-700"><i class="las la-filter"></i></button>
                </div>
            </div>
        </div>
        <div class="flex">
            <div class="overflow-x-auto flex-auto">
                <table class="table w-full" v-if="Object.values( columns ).length > 0">
                    <thead>
                        <tr class="text-gray-700 border-b border-gray-200">
                            <th class="text-center px-2 border-gray-200 bg-gray-100 border w-16 py-2">
                                <ns-checkbox :checked="globallyChecked" @change="handleGlobalChange( $event )"></ns-checkbox>
                            </th>
                            <th @click="sort( identifier )" v-for="(column, identifier) of columns" :style="{ 'min-width' : column.width || 'auto' }" class="cursor-pointer justify-betweenw-40 border bg-gray-100 text-left px-2 border-gray-200 py-2">
                                <div class="w-full flex justify-between items-center">
                                    <span class="flex">{{ column.label }}</span>
                                    <span class="h-6 w-6 flex justify-center items-center">
                                        <i v-if="column.$direction === 'desc'" class="las la-sort-amount-up"></i>
                                        <i v-if="column.$direction === 'asc'" class="las la-sort-amount-down"></i>
                                    </span>
                                </div>
                            </th>
                            <th class="text-left px-2 py-2 w-16 border border-gray-200 bg-gray-100"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="result.data !== undefined && result.data.length > 0">
                            <ns-table-row @updated="refresh()" v-for="row of result.data" :columns="columns" :row="row" @toggled="handleShowOptions( $event )"></ns-table-row>
                        </template>
                        <tr v-if="! result || result.data.length === 0">
                            <td :colspan="Object.values( columns ).length + 2" class="text-center text-gray-600 py-3">There is nothing to display...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="p-2 flex justify-between">
            <div id="grouped-actions" class="flex justify-between rounded-full bg-gray-200 p-1">
                <select class="text-gray-800 outline-none bg-transparent" v-model="bulkAction" id="grouped-actions">
                    <option selected value=""><slot name="bulk-label">bulk-label</slot></option>
                    <option v-for="action of bulkActions" :value="action.identifier">{{ action.label }}</option>
                </select>
                <button @click="bulkDo()" class="h-8 w-8 outline-none hover:bg-blue-400 hover:text-white rounded-full bg-white flex items-center justify-center"><slot name="bulk-go">Go</slot></button>
            </div>
            <div id="pagination" class="flex -mx-1">
                <template v-if="result.current_page">
                    <a href="javascript:void(0)" @click="page=result.first_page;refresh()" class="mx-1 flex items-center justify-center h-8 w-8 rounded-full bg-gray-200 text-gray-700 hover:bg-blue-400 hover:text-white shadow">
                        <i class="las la-angle-double-left"></i>
                    </a>
                    <template v-for="_paginationPage of pagination">
                        <a v-if="page !== '...'" :class="page == _paginationPage ? 'bg-blue-400 text-white' : 'bg-gray-200 text-gray-700'" @click="page=_paginationPage;refresh()" href="javascript:void(0)" class="mx-1 flex items-center justify-center h-8 w-8 rounded-full hover:bg-blue-400 hover:text-white">{{ _paginationPage }}</a>
                        <a v-if="page === '...'" href="javascript:void(0)" class="mx-1 flex items-center justify-center h-8 w-8 rounded-full bg-gray-200 text-gray-700">...</a>
                    </template>
                    <a href="javascript:void(0)" @click="page=result.last_page;refresh()" class="mx-1 flex items-center justify-center h-8 w-8 rounded-full bg-gray-200 text-gray-700 hover:bg-blue-400 hover:text-white shadow">
                        <i class="las la-angle-double-right"></i>
                    </a>
                </template>
            </div>
        </div>
    </div>
    `,
});

export { nsCrud };