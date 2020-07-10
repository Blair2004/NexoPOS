const { Vue, nsEvent }   =   require( './../bootstrap' );

Vue.component( 'ns-table-row', {
    props: [
        'options', 'row'
    ],
    data: () => {
        return {
            optionsToggled: false
        }
    },
    methods: {
        toggleMenu() {
            this.row.$toggled   =   !this.row.$toggled;
            this.$emit( 'toggled', this.row );
        },
        handleChanged( event ) {
            this.row.$checked   =   event;
        }
    },
    template: `
    <template>
        <tr class="border-gray-200 border text-sm">
            <td class="text-gray-700 font-sans border-gray-200 p-2">
                <ns-checkbox @change="handleChanged( $event )" :checked="row.$checked"></ns-checkbox>
            </td>
            <td class="text-gray-700 font-sans border-gray-200 p-2">E-21</td>
            <td class="text-gray-700 font-sans border-gray-200 p-2">Product-31</td>
            <td class="text-gray-700 font-sans border-gray-200 p-2">Product-41</td>
            <td class="text-gray-700 font-sans border-gray-200 p-2">Product-51</td>
            <td class="text-gray-700 font-sans border-gray-200 p-2">
                <button @click="toggleMenu()" class="outline-none rounded-full w-24 text-sm p-1 border border-gray-400 hover:bg-blue-400 hover:text-white hover:border-transparent"><i class="las la-ellipsis-h"></i> Options</button>
                <div v-if="row.$toggled" class="rounded shadow-lg mt-1 bg-gray-100 overflow-hidden w-32 absolute -mx-8">
                    <ul>
                        <li class=""><a href="#" class="px-4 py-2 block hover:bg-blue-400 hover:text-white">Edit</a></li>
                        <li class=""><a href="#" class="px-4 py-2 block hover:bg-blue-400 hover:text-white">Delete</a></li>
                    </ul>
                </div>
            </td>
        </tr>
    </template>
    `
})