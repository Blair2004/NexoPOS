import { __ } from '@/libraries/lang';
import { remove } from 'lodash';
import Vue from 'vue';
import { nsEvent } from './../bootstrap';

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
        __,
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
    mounted() {
        if ( this.field.value ) {
            const values     =   this.field.value.reverse();
            values.forEach( value => {
                const option     =   this.field.options.filter( option => option.value === value );

                if ( option.length >= 0 ) {
                    this.addOption( option[0] );
                }
            })
        }
    },
    template: `
    <div class="flex flex-col">
        <label :for="field.name" :class="hasError ? 'text-error-primary' : 'text-primary'" class="block mb-1 leading-5 font-medium"><slot></slot></label>
        <div class="flex flex-col">
            <div @click="showPanel = !showPanel" :class="showPanel ? '' : ''" class="select-preview flex justify-between rounded border-2 border-input-option-hover p-2 items-center">
                <div class="flex -mx-1 -my-1 flex-wrap">
                    <div class="px-1 my-1" v-for="(option,index) of _options.filter( o => o.selected )">
                        <div class="rounded bg-info-secondary text-white flex justify-between p-1 items-center">
                            <span class="pr-8">{{ option.label }}</span>
                            <button @click="removeOption( option, $event )" class="rounded outline-none hover:bg-info-tertiary h-6 w-6 flex items-center justify-center">
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
                <div class="bg-input-edge shadow">
                    <div class="search border-b border-input-option-hover">
                        <input v-model="search" class="p-2 w-full bg-transparent text-primary outline-none" placeholder="Search">
                    </div>
                    <div class="h-40 overflow-y-auto">
                        <div @click="addOption( option )" v-for="(option, index) of _options" :class="option.selected ? 'bg-info-secondary text-white' : 'text-primary'" class="option p-2 flex justify-between cursor-pointer hover:bg-info-secondary hover:text-white">
                            <span>{{ option.label }}</span>
                            <span>
                                <i v-if="option.checked" class="las la-check"></i>
                            </span>
                        </div>
                    </div>
                    <div v-if="_options.length === 0" class="p-2 text-center text-primary">{{ __( 'Nothing to display' ) }}</div>
                </div>
            </div>
        </div>
        <div class="my-2">
            <p v-if="! field.errors || field.errors.length === 0" class="text-xs text-secondary"><slot name="description"></slot></p>
            <p v-for="error of field.errors" class="text-xs text-error-primary">
                <slot v-if="error.identifier === 'required'" :name="error.identifier">{{ __( 'This field is required.' ) }}</slot>
                <slot v-if="error.identifier === 'email'" :name="error.identifier">{{ __( 'This field must contain a valid email address.' ) }}</slot>
                <slot v-if="error.identifier === 'invalid'" :name="error.identifier">{{ error.message }}</slot>
            </p>
        </div>
    </div>
    `,
})

export { nsMultiselect }