const { Vue, nsEvent }      =   require( './../bootstrap' );
const { remove } = require('lodash');

const nsMultiselect         =   Vue.component( 'ns-multiselect', {
    data() {
        return {
            showPanel: false,
        }
    },
    props: [ 'options' ],
    methods: {
        addOption( option ) {
            this.$emit( 'addOption', option );
            this.$forceUpdate();
        },
        removeOption( option, index, event ) {
            event.preventDefault();
            event.stopPropagation();
            this.$emit( 'removeOption', { option, index } );
            this.$forceUpdate();
            return false;
        }
    },
    mounted() {},
    template: `
    <div class="bg-white flex flex-col" :class="showPanel ? 'shadow' : ''">
        <div @click="showPanel = !showPanel" :class="showPanel ? 'm-2' : ''" class="select-preview flex justify-between rounded border-2 border-gray-200 p-2 items-center">
            <div class="flex -mx-1 flex-wrap">
                <div class="px-1 mb-1" v-for="(option,index) of options.filter( o => o.selected )">
                    <div class="rounded bg-blue-400 text-white flex justify-between p-1 items-center">
                        <span class="pr-8">{{ option.label }}</span>
                        <button @click="removeOption( option, index, $event )" class="rounded outline-none hover:bg-blue-500 h-6 w-6 flex items-center justify-center">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div>
                <i class="las la-angle-down" v-if="showPanel"></i>
                <i class="las la-angle-up" v-if="!showPanel"></i>
            </div>
        </div>
        <div class="h-0" v-if="showPanel">
            <div class="bg-white shadow">
                <div class="search border-b border-gray-200">
                    <input class="p-2 w-full text-gray-600 outline-none" placeholder="Search">
                </div>
                <div class="h-40 overflow-y-auto">
                    <div @click="addOption( option )" v-for="option of options" :class="option.selected ? 'bg-blue-300 text-white' : 'text-gray-600'" class="option p-2 flex justify-between cursor-pointer hover:bg-blue-200 hover:text-white">
                        <span>{{ option.label }}</span>
                        <span>
                            <i v-if="option.checked" class="las la-check"></i>
                        </span>
                    </div>
                </div>
                <div v-if="options.length === 0" class="p-2 text-center text-gray-400">Nothing to display</div>
            </div>
        </div>
        </div>
    `,
})

module.exports  =   nsMultiselect;