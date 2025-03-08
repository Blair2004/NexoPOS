<template>
    <div class="flex flex-col ns-multiselect" :class="hasError ? 'has-error' : 'is-pristine'">
        <label :for="field.name"  class="block mb-1 leading-5 font-medium"><slot></slot></label>
        <div class="flex flex-col">
            <div @click="togglePanel()" :class="( field.disabled ? 'disabled' : '') + ' ' + ( showPanel ? 'toggled' : 'untoggled' )" style="max-height: 150px;" class="overflow-y-auto flex select-preview justify-between border p-2 items-start">
                <div class="flex -mx-1 -my-1 flex-wrap">
                    <div :key="index" class="px-1 my-1" v-for="(option,index) of _options.filter( o => o.selected )">
                        <div class="rounded selected-pills flex justify-between p-1 items-center">
                            <span class="pr-8 text-xs">{{ option.label }}</span>
                            <button @click="removeOption( option, $event )" class="rounded outline-hidden hover:bg-info-tertiary h-4 w-4 flex items-center justify-center">
                                <i class="las la-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="arrows ml-1">
                    <span :class="showPanel ? '' : 'hidden'"><i class="las la-angle-down"></i></span>
                    <span :class="showPanel ? 'hidden': ''"><i class="las la-angle-up"></i></span>
                </div>
            </div>
            <div class="h-0 z-10" style="margin-top: -2px; margin-bottom: 2px;" v-if="showPanel" :class="showPanel ? 'shadow' : ''">
                <div class="ns-dropdown shadow border rounded-b-md">
                    <div class="search border-b border-input-option-hover">
                        <input @keypress.enter="selectAvailableOptionIfPossible()" v-model="search" class="p-2 w-full bg-transparent text-fontcolor outline-hidden" placeholder="Search">
                    </div>
                    <div class="h-40 overflow-y-auto">
                        <div @click="addOption( option )" :key="index" v-for="(option, index) of _filtredOptions" :class="option.selected ? 'selected' : ''" class="option p-2 flex justify-between cursor-pointer">
                            <span>{{ option.label }}</span>
                            <span>
                                <i v-if="option.checked" class="las la-check"></i>
                            </span>
                        </div>
                    </div>
                    <div v-if="_options.length === 0" class="p-2 text-center text-fontcolor">{{ __( 'Nothing to display' ) }}</div>
                </div>
            </div>
        </div>
        <div class="my-2">
            <ns-field-description :field="field"></ns-field-description>
        </div>
    </div>
</template>
<script lang="ts">
import { __ } from '~/libraries/lang';

declare const nsEvent;

export default {
    data() {
        return {
            showPanel: false,
            search: '',
            eventListener: null,
        }
    },
    emits: [ 'change', 'blur' ],
    props: [ 'field' ],
    computed: {
        hasError() {
            if ( this.field.errors !== undefined && this.field.errors.length > 0 ) {
                return true;
            }
            return false;
        },
        _filtredOptions() {
            let options     =   this._options;

            if ( this.search.length > 0 ) {
                options     =   this._options.filter( options => {
                    return options.label.toLowerCase().search( this.search.toLowerCase() ) !== -1;
                });
            }
            
            return options.filter( option => option.selected === false );
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
        togglePanel() {
            if ( ! this.field.disabled ) {
                this.showPanel = !this.showPanel;
            }
        },
        selectAvailableOptionIfPossible() {
            if ( this._filtredOptions.length > 0 ) {
                this.addOption( this._filtredOptions[0] );
            }
        },
        addOption( option ) {
            if ( ! this.field.disabled ) {
                this.$emit( 'addOption', option );
                this.$forceUpdate();

                setTimeout( () => {
                    this.search     =   '';
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

                if ( option.length > 0 ) {
                    this.addOption( option[0] );
                }
            });
        }

        this.eventListener  =   document.addEventListener( 'click', (e) => {
            /**
             * applicable only if the panel is toggled
             */
            const element = e.target;
            let ancestor = element.parentElement;
            let isSelect    =   false;

            if ( this.showPanel ) {
                
                while (ancestor) {
                    if ( ancestor && ancestor.classList.contains('ns-multiselect') && ! ancestor.classList.contains('arrows')  ) {
                        isSelect    =   true;
                        break;
                    }
                    ancestor = ancestor.parentElement;
                }

                if ( isSelect === false ) {
                    this.togglePanel();
                }
            }
        })
    },
}
</script>