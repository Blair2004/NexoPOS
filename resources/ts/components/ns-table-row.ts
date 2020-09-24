import Vue from 'vue';

const { nsEvent, nsHttpClient, nsSnackBar }   =   require( './../bootstrap' );

const nsTableRow    =   Vue.component( 'ns-table-row', {
    props: [
        'options', 'row', 'columns'
    ],
    data: () => {
        return {
            optionsToggled: false
        }
    },
    mounted() {
    },
    methods: {
        getElementOffset(el) {
            const rect = el.getBoundingClientRect();
            
            return {
                top: rect.top + window.pageYOffset,
                left: rect.left + window.pageXOffset,
            };
        },
        toggleMenu( element ) {
            this.row.$toggled   =   !this.row.$toggled;
            this.$emit( 'toggled', this.row );

            if ( this.row.$toggled ) {
                setTimeout(() => {
                    // const contextMenu               =   element.target.parentElement.lastElementChild;
                    // const offset                    =   this.getElementOffset( element.target );
                    // contextMenu.style.top           =   ( offset.top + element.target.offsetHeight ) + 'px';
                    // contextMenu.style.right         =   ( offset.right - element.target.offsetWidth ) + 'px';
                    // console.log( offset.left - element.target.offsetWidth );
                }, 100 );
            }
        },
        handleChanged( event ) {
            this.row.$checked   =   event;
        },
        triggerAsync( action ) {
            if ( action.confirm ) {
                if ( confirm( action.confirm.message ) ) {
                    nsHttpClient[ action.type.toLowerCase() ]( action.url )
                        .subscribe( response => {
                            nsSnackBar.success( response.message )
                                .subscribe();
                            this.$emit( 'updated', true );
                        }, ( response ) => {
                            this.toggleMenu();
                            nsSnackBar.error( response.message ).subscribe();
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
        <td class="text-gray-700 font-sans border-gray-200 p-2 flex flex-col items-start justify-center">
            <button @click="toggleMenu( $event )" class="outline-none rounded-full w-24 text-sm p-1 border border-gray-400 hover:bg-blue-400 hover:text-white hover:border-transparent"><i class="las la-ellipsis-h"></i> Options</button>
            <div @click="toggleMenu( $event )" v-if="row.$toggled" class="absolute w-full h-full z-10 top-0 left-0"></div>
            <div class="relative">
                <div v-if="row.$toggled" class="z-50 origin-bottom-right -ml-32 w-56 mt-2 absolute rounded-md shadow-lg">
                    <div class="rounded-md bg-white shadow-xs">
                        <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                            <template v-for="action of row.$actions">
                                <a :href="action.url" v-if="action.type === 'GOTO'" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">{{ action.label }}</a>
                                <a href="javascript:void(0)" @click="triggerAsync( action )" v-if="[ 'GET', 'DELETE' ].includes( action.type )" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">{{ action.label }}</a>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>
    `
})

export { nsTableRow }