const { Vue, nsEvent }      =   require( './../bootstrap' );
const { remove } = require('lodash');

const nsMultiselect         =   Vue.component( 'ns-multiselect', {
    data() {
        return {
            showPanel: false,
            search: '',
        }
    },
    props: [ 'field' ],
    computed: {
        hasError() {
            if ( this.field.errors !== undefined && this.field.errors.length > 0 ) {
                return true;
            }
            return false;
        },
        _options() {
            return this.field.options.map( option => {
                option.selected     =   option.selected === undefined ? false : option.selected;
                if ( this.field.value && this.field.value.includes( option.value ) ) {
                    option.selected     =   true;
                }
                return option;
            });
        },
    },
    methods: {
        addOption( option ) {
            if ( ! this.field.disabled ) {
                this.$emit( 'addOption', option );
                this.$forceUpdate();
                setTimeout( () => {
                    // this.search     =   '';
                }, 100 );
            }
        },
        removeOption( option, event ) {
            if ( ! this.field.disabled ) {
                event.preventDefault();
                event.stopPropagation();
                this.$emit( 'removeOption', option );
                this.$forceUpdate();
                setTimeout( () => {
                    this.search     =   '';
                }, 100 );
                return false;
            }
        }
    },
    mounted() {},
    template: `
    <div class="flex flex-col">
        <label :for="field.name" :class="hasError ? 'text-red-700' : 'text-gray-700'" class="block mb-1 leading-5 font-medium"><slot></slot></label>
        <div class="bg-white flex flex-col">
            <div @click="showPanel = !showPanel" :class="showPanel ? '' : ''" class="select-preview flex justify-between rounded border-2 border-gray-200 p-2 items-center">
                <div class="flex -mx-1 -my-1 flex-wrap">
                    <div class="px-1 my-1" v-for="(option,index) of _options.filter( o => o.selected )">
                        <div class="rounded bg-blue-400 text-white flex justify-between p-1 items-center">
                            <span class="pr-8">{{ option.label }}</span>
                            <button @click="removeOption( option, $event )" class="rounded outline-none hover:bg-blue-500 h-6 w-6 flex items-center justify-center">
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
            <div class="h-0 z-10" v-if="showPanel" :class="showPanel ? 'shadow' : ''">
                <div class="bg-white shadow">
                    <div class="search border-b border-gray-200">
                        <input v-model="search" class="p-2 w-full text-gray-600 outline-none" placeholder="Search">
                    </div>
                    <div class="h-40 overflow-y-auto">
                        <div @click="addOption( option )" v-for="(option, index) of _options" :class="option.selected ? 'bg-blue-300 text-white' : 'text-gray-600'" class="option p-2 flex justify-between cursor-pointer hover:bg-blue-200 hover:text-white">
                            <span>{{ option.label }}</span>
                            <span>
                                <i v-if="option.checked" class="las la-check"></i>
                            </span>
                        </div>
                    </div>
                    <div v-if="_options.length === 0" class="p-2 text-center text-gray-400">Nothing to display</div>
                </div>
            </div>
        </div>
        <div class="my-2">
            <p v-if="! field.errors || field.errors.length === 0" class="text-xs text-gray-500"><slot name="description"></slot></p>
            <p v-for="error of field.errors" class="text-xs text-red-400">
                <slot v-if="error.identifier === 'required'" :name="error.identifier">This field is required.</slot>
                <slot v-if="error.identifier === 'email'" :name="error.identifier">This field must contain a valid email address.</slot>
                <slot v-if="error.identifier === 'invalid'" :name="error.identifier">{{ error.message }}</slot>
            </p>
        </div>
    </div>
    `,
})

module.exports  =   nsMultiselect;