const { Vue, EventEmitter, nsHttpClient }   =   require( './../bootstrap' );

const nsCrud    =   Vue.component( 'ns-crud', {
    data: () => {
        return {
            columns: [],
            globallyChecked: false,
            rows: []
        }
    }, 
    mounted() {
        this.loadColumns();
    },
    props: [ 'url' ],
    methods: {
        handleShowOptions( e ) {
            this.rows.forEach( row => {
                if ( row.$id !== e.$id ) {
                    row.$toggled    =   false;
                }
            });
        },
        handleGlobalChange( event ) {
            this.globallyChecked    =   event;
            this.rows.forEach( r => r.$checked = event );
        },
        loadColumns() {
            const request   =   nsHttpClient.get( `${this.url}/columns` );
            request.subscribe( f => {
                this.columns    =   f.data;
                this.refresh();
            });
        },
        refresh() {
            const request   =   nsHttpClient.get( `${this.url}` );
            request.subscribe( f => {
                this.rows    =   f.data;
            });
        }
    },
    template: `
    <div id="crud-table" class="w-full shadow rounded-lg bg-white">
        <div id="crud-table-header" class="p-2 border-b border-gray-200 flex justify-between flex-wrap">
            <div id="crud-search-box" class="w-full md:w-auto -mx-2 flex">
                <div class="px-2 flex">
                    <a class="rounded-full hover:border-blue-400 hover:text-white hover:bg-blue-400 text-sm h-10 bg-white px-3 outline-none text-gray-800 border border-gray-400"><i class="las la-plus"></i></a>
                </div>
                <div class="px-2">
                    <div class="rounded-full p-1 bg-gray-200 flex">
                        <input type="text" class="bg-transparent outline-none px-2">
                        <button class="rounded-full w-8 h-8 bg-white outline-none hover:bg-blue-400 hover:text-white"><i class="las la-search"></i></button>
                    </div>
                </div>
                <div class="px-2 flex">
                    <button @click="refresh()" class="rounded-full hover:border-blue-400 hover:text-white hover:bg-blue-400 text-sm h-10 bg-white px-3 outline-none text-gray-800 border border-gray-400"><i class="las la-sync"></i> </button>
                </div>
            </div>
            <div id="crud-buttons" class="-mx-1 flex flex-wrap w-full md:w-auto">
                <div class="px-1 flex">
                    <button class="rounded-full text-sm h-10 bg-teal-400 px-4 outline-none text-white font-semibold"><i class="las la-download"></i></button>
                </div>
                <div class="px-1 flex">
                    <button class="rounded-full text-sm h-10 bg-green-400 px-4 outline-none text-white font-semibold"><i class="las la-upload"></i></button>
                </div>
                <div class="px-1 flex">
                    <button class="rounded-full text-sm h-10 hover:border-blue-400 hover:text-white hover:bg-blue-400 outline-none border-gray-400 border text-gray-700 px-4"><i class="las la-filter"></i></button>
                </div>
            </div>
        </div>
        <div class="flex">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr class="text-gray-700 border-b border-gray-200">
                            <th class="text-center px-2 border-gray-200 w-16 py-2">
                                <ns-checkbox :checked="globallyChecked" @change="handleGlobalChange( $event )"></ns-checkbox>
                            </th>
                            <th v-for="column of columns" class="text-left px-2 border-gray-200 py-2">{{ column.label }}</th>
                            <th class="text-left px-2 border-gray-200 py-2 w-16"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <ns-table-row v-for="row of rows" :columns="columns" :row="row" @toggled="handleShowOptions( $event )"></ns-table-row>
                        <tr>
                            <td :colspan="Object.values( columns ).length" class="text-center text-gray-600 py-3">There is nothing to display...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    `,
});

module.exports   =   nsCrud;