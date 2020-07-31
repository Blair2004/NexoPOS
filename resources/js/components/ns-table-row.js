const { Vue, nsEvent, nsHttpClient, nsSnackBar }   =   require( './../bootstrap' );

Vue.component( 'ns-table-row', {
    props: [
        'options', 'row', 'columns'
    ],
    data: () => {
        return {
            optionsToggled: false
        }
    },
    mounted() {
        console.log( this.row, this.columns );
    },
    methods: {
        toggleMenu() {
            this.row.$toggled   =   !this.row.$toggled;
            this.$emit( 'toggled', this.row );
        },
        handleChanged( event ) {
            this.row.$checked   =   event;
        },
        triggerAsync( action ) {
            if ( action.confirm ) {
                if ( confirm( action.confirm.message ) ) {
                    nsHttpClient[ action.type.toLowerCase() ]( action.url )
                        .subscribe( response => {
                            console.log( response );
                            nsSnackBar.success( response.data.message )
                                .subscribe();
                            this.$emit( 'updated', true );
                        }, ( response ) => {
                            console.log( Object.keys( response ) );
                        })
                }
            }
        }
    },
    template: `
    <tr class="border-gray-200 border text-sm">
        <td class="text-gray-700 font-sans border-gray-200 p-2">
            <ns-checkbox @change="handleChanged( $event )" :checked="row.$checked"> </ns-checkbox>
        </td>
        <td v-for="(column, identifier) of columns" class="text-gray-700 font-sans border-gray-200 p-2">{{ row[ identifier ] }}</td>
        <td class="text-gray-700 font-sans border-gray-200 p-2 flex flex-col items-end justify-center">
            <button @click="toggleMenu()" class="outline-none rounded-full w-24 text-sm p-1 border border-gray-400 hover:bg-blue-400 hover:text-white hover:border-transparent"><i class="las la-ellipsis-h"></i> Options</button>
            
            <div v-if="row.$toggled" class="origin-bottom-right absolute right-4 mt-16 w-56 rounded-md shadow-lg">
                <div class="rounded-md bg-white shadow-xs">
                    <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                        <template v-for="action of row.$actions">
                            <a :href="action.url" v-if="action.type === 'GOTO'" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">{{ action.label }}</a>
                            <a href="javascript:void(0)" @click="triggerAsync( action )" v-if="[ 'GET', 'DELETE' ].includes( action.type )" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">{{ action.label }}</a>
                        </template>
                    </div>
                </div>
            </div>
        </td>
    </tr>
    `
})