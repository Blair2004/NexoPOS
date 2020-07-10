const { Vue, EventEmitter, nsHttpClient }   =   require( './../bootstrap' );

const nsCrud    =   Vue.component( 'ns-crud', {
    data: () => {
        return {
            columns: [],
            globallyChecked: false,
            rows: [{
                $id: 1,
                $toggled: false,
                $checked: false,
                name: 'Foo Product',
                price: 200,
                quantity: 20
            }, {
                $id: 2,
                $toggled: false,
                $checked: false,
                name: 'Bar Product',
                price: 22,
                quantity: 41
            }]
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
            const request   =   nsHttpClient.get( this.url );
            request.subscribe( f => {
                this.columns    =   f.data;
            });
        }
    },
    template: `
    <table class="table w-full">
        <thead>
            <tr class="text-gray-700 border-b border-gray-200">
                <th class="text-center px-2 border-gray-200 w-16 py-2">
                    <ns-checkbox :checked="globallyChecked" @change="handleGlobalChange( $event )"></ns-checkbox>
                </th>
                <th v-for="column of columns" class="text-left px-2 border-gray-200 py-2">{{ column.label }}</th>
                <th class="text-left px-2 border-gray-200 py-2">Name</th>
                <th class="text-left px-2 border-gray-200 py-2">Price</th>
                <th class="text-left px-2 border-gray-200 py-2">Quantity</th>
                <th class="text-left px-2 border-gray-200 py-2 w-16"></th>
            </tr>
        </thead>
        <tbody>
            <ns-table-row v-for="row of rows" :row="row" @toggled="handleShowOptions( $event )"></ns-table-row>
        </tbody>
    </table>
    `,
});

module.exports   =   nsCrud;