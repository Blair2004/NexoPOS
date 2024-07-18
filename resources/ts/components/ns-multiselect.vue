<template>
    <div class="flex flex-col ns-multiselect">
        <label :for="field.name" :class="hasError ? 'text-error-secondary' : 'text-primary'" class="block mb-1 leading-5 font-medium"><slot></slot></label>
        <div class="flex flex-col">
            <div @click="togglePanel()" :class="field.disabled ? 'bg-input-disabled' : 'bg-input-background'" style="max-height: 150px;" class="overflow-y-auto flex select-preview justify-between rounded border-2 border-input-edge p-2 items-start">
                <div class="flex -mx-1 -my-1 flex-wrap">
                    <div :key="index" class="px-1 my-1" v-for="(option,index) of _options.filter( o => o.selected )">
                        <div class="rounded bg-info-secondary text-white flex justify-between p-1 items-center">
                            <span class="pr-8">{{ option.label }}</span>
                            <button @click="removeOption( option, $event )" class="rounded outline-none hover:bg-info-tertiary h-6 w-6 flex items-center justify-center">
                                <i class="las la-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="arrows ml-1">
                    <i class="las la-angle-down" :class=" showPanel ? 'hidden' : ''"></i>
                    <i class="las la-angle-up" :class=" !showPanel ? 'hidden' : ''"></i>
                </div>
            </div>
            <div class="h-0 z-10" style="margin-top: -5px;" v-if="showPanel" :class="showPanel ? 'shadow' : ''">
                <div class="ns-dropdown shadow border-2 rounded-b-md border-input-edge bg-input-background">
                    <div class="search border-b border-input-option-hover">
                        <input @keypress.enter="selectAvailableOptionIfPossible()" v-model="search" class="p-2 w-full bg-transparent text-primary outline-none" placeholder="Search">
                    </div>
                    <div class="h-40 overflow-y-auto">
                        <div @click="addOption( option )" :key="index" v-for="(option, index) of _filtredOptions" :class="option.selected ? 'bg-info-secondary text-white' : 'text-primary'" class="option p-2 flex justify-between cursor-pointer hover:bg-info-tertiary hover:text-white">
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