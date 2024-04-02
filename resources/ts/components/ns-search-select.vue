<template>
    <div class="flex flex-col flex-auto ns-select">
        <label :for="field.name" :class="hasError ? 'has-error' : 'is-pristine'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="( hasError ? 'has-error' : 'is-pristine' ) + ' ' + ( field.disabled ? 'cursor-not-allowed' : 'cursor-default' )" class="border-2 mt-1 relative rounded-md shadow-sm mb-1 flex overflow-hidden">
            <div @click="! field.disabled && (showResults = ! showResults)" :class="( field.disabled ? 'bg-input-disabled' : 'bg-input-background' )" class="flex-auto h-10 sm:leading-5 py-2 px-4 flex items-center">
                <span class="text-primary text-sm">{{ selectedOptionLabel }}</span>
            </div>
            <div v-if="field.component && ! field.disabled" @click="triggerDynamicComponent( field )" class="flex items-center justify-center w-10 hover:cursor-pointer hover:bg-input-button-hover border-l-2 border-input-edge">
                <i class="las la-plus"></i>
            </div>
        </div>
        <div class="relative" v-if="showResults">
            <div class="w-full overflow-hidden -top-[8px] border-r-2 border-l-2 border-t rounded-b-md border-b-2 border-input-edge bg-input-background shadow z-10 absolute">
                <div class="border-b border-input-edge border-dashed p-2">
                    <input @keypress.enter="selectFirstOption()" ref="searchInputField" v-model="searchField" type="text" :placeholder="__( 'Search result' )">
                </div>
                <div class="h-60 overflow-y-auto">
                    <ul>
                        <li @click="selectOption( option )" v-for="option of filtredOptions" class="py-1 px-2 hover:bg-info-primary cursor-pointer text-primary">{{ option.label }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <ns-field-description :field="field"></ns-field-description>
    </div>
</template>
<script lang="ts">
import { nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { Popup } from '~/libraries/popup';

declare const nsExtraComponents: any;
declare const nsComponents: any;

export default {
    data: () => {
        return {
            searchField: '',
            showResults: false,
        }
    },
    name: 'ns-search-select',
    emits: [ 'saved', 'change' ],
    props: [ 'name', 'placeholder', 'field', 'leading' ],
    computed: {
        selectedOptionLabel() {
            if ( this.field.value === null || this.field.value === undefined ) {
                return __( 'Choose...' );
            }

            const options   =   this.field.options.filter( option => option.value === this.field.value );

            if ( options.length > 0 ) {
                return options[0].label;
            }

            return __( 'Choose...' );
        },
        filtredOptions() {
            if ( this.searchField.length > 0 ) {
                return this.field.options.filter( option => {
                    const expression    =   ( new RegExp( this.searchField, 'i' ) );
                    
                    return expression.test( option.label );
                }).splice(0,10);
            } else {
                return this.field.options;
            }
        },
        hasError() {
            if ( this.field.errors !== undefined && this.field.errors.length > 0 ) {
                return true;
            }
            return false;
        },
        disabledClass() {
            return this.field.disabled ? 'ns-disabled cursor-not-allowed' : '';
        },
        inputClass() {
            return this.disabledClass + ' ' + this.leadClass
        },
        leadClass() {
            return this.leading ? 'pl-8' : 'px-4';
        }
    },
    watch: {
        showResults() {
            if ( this.showResults === true ) {
                setTimeout( () => {
                    this.$refs.searchInputField.select();
                }, 50 );
            }
        }
    },
    mounted() {
        const options   =   this.field.options.filter( op => op.value === this.field.value );

        if ( options.length > 0 && [ null, undefined ].includes( this.field.value ) ) {
            this.selectOption( options[0] );
        }

        document.addEventListener( 'click', ( event ) => {
            if ( this.$el.contains( event.target ) === false ) {
                this.showResults    =   false;
            }
        });
    },
    methods: { 
        __,
        selectFirstOption() {
            if ( this.filtredOptions.length > 0 ) {
                this.selectOption( this.filtredOptions[0] );
            }
        },
        selectOption( option ) {
            this.field.value    =   option.value;
            this.$emit( 'change', option.value );
            this.searchField    =   '';
            this.showResults    =   false;
        },
        async triggerDynamicComponent( field ) {
            try {
                this.showResults    =   false;
                const component =   nsExtraComponents[ field.component ] || nsComponents[ field.component];

                if ( component === undefined ) {
                    nsSnackBar.error( __( `The component ${field.component} cannot be loaded. Make sure it's injected on nsExtraComponents object.` ) ).subscribe();
                }

                const result = await new Promise( ( resolve, reject ) => {
                    const response  =   Popup.show( component, { ...( field.props || {}), field: this.field, resolve, reject } );
                });

                this.$emit( 'saved', result );
            } catch ( error ) {
                // probably the popup is closed
            }
        },
    },
}
</script>