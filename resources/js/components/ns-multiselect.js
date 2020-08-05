const { Vue, nsEvent }      =   require( './../bootstrap' );

const nsMultiselect         =   Vue.component( 'ns-multiselect', {
    data() {
        return {
            showPanel: false,
        }
    },
    props: [ 'options' ],
    computed: {
        realOptions() {
            if ( this.options ) {
                return this.options.map( option => {
                    option.selected     =   option.selected || false;
                    return option;
                });
            }
            return [];
        },
        selected() {
            if ( this.options ) {
                return this.options.filter( o => o.selected );
            }
            return [];
        }
    },
    mounted() {
        console.log( this.options );
    },
    template: `
    <div class="bg-white flex flex-col" :class="showPanel ? 'shadow' : ''">
        <div @click="showPanel = !showPanel" :class="showPanel ? 'm-2' : ''" class="select-preview flex justify-between rounded border-2 border-gray-200 p-2 items-center">
            <div>
                <div v-for="(tag,index) of selected" class="rounded bg-blue-400 text-white flex justify-between p-1 items-center">
                    <span class="pr-8">{{ tag.label }}</span>
                    <button @click="$emit( 'remove', {tag, index} )" class="rounded outline-none hover:bg-blue-500 h-6 w-6 flex items-center justify-center">x</button>
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
                    <div @click="$emit( 'add', option )" v-for="option of realOptions" :class="option.selected ? 'bg-blue-300' : ''" class="option p-2 text-gray-600 flex justify-between cursor-pointer hover:bg-gray-100">
                        <span>{{ option.label }}</span>
                        <span>
                            <i v-if="option.checked" class="las la-check"></i>
                        </span>
                    </div>
                </div>
                <div v-if="realOptions.length === 0" class="p-2 text-center text-gray-400">Nothing to display</div>
            </div>
        </div>
        </div>
    `,
})

module.exports  =   nsMultiselect;