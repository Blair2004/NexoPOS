<template>
    <div class="flex flex-col flex-auto ns-select" v-if="field" :class="( hasError ? 'has-error' : 'is-pristine' )" :disabled="field.disabled">
        <label :for="field.name" class="block leading-5 font-medium"><slot></slot></label>
        <div :name="field.name" :class="( field.disabled ? 'cursor-not-allowed' : 'cursor-default' ) + ' ' + ( showResults ? 'rounded-t-md' : 'rounded-md')" 
            class="border mt-1 relative shadow-sm mb-1 flex overflow-hidden">
            <div @click="! field.disabled && (showResults = ! showResults)"
                class="placeholder flex-auto h-10 sm:leading-5 py-2 px-4 flex items-center">
                <span class="text-fontcolor text-sm">{{ selectedOptionLabel }}</span>
            </div>
            <button type="button" v-if="hasSelectedValues( field ) && ! field.disabled" @click="resetSelectedInput( field )" class="flex items-center justify-center w-10 border-l hover:cursor-pointer hover:bg-error-tertiary hover:text-white border-input-edge">
                <i class="las la-times"></i>
            </button>
            <button type="button" v-if="field.component && ! field.disabled" @click="triggerDynamicComponent( field )" class="flex items-center justify-center w-10 hover:cursor-pointer border-l">
                <i class="las la-plus"></i>
            </button>
            <button type="button" v-if="field.about" @click="triggerFieldAbout( field )" class="flex items-center justify-center w-10 hover:cursor-pointer border-l">
                <i class="las la-question-circle text-2xl text-font"></i>
            </button>
        </div>
        <div class="relative" v-if="showResults">
            <div class="w-full overflow-hidden -top-[5px] ns-select-results border rounded-b-md shadow z-10 absolute">
                <div class="border-b border-dashed p-2 filter-input-wrapper">
                    <input @keypress.enter.prevent="selectFirstOption()" @input="handleSearchInput()" ref="searchInputField" v-model="searchField" type="text" :placeholder="__( 'Search result' )">
                </div>
                <div class="h-60 overflow-y-auto">
                    <ul>
                        <li @click="selectOption( option )" v-for="option of filtredOptions" :key="option.value" class="py-1 px-2 hover:bg-info-primary cursor-pointer text-font">{{ option.label }}</li>
                        <li v-if="asyncSearchLoading" class="py-2 px-2 text-sm text-fontcolor-soft">{{ __( 'Loading options...' ) }}</li>
                        <li v-else-if="isAsyncSearch && filtredOptions.length === 0" class="py-2 px-2 text-sm text-fontcolor-soft">{{ __( 'No matching options found.' ) }}</li>
                        <li v-if="showAsyncSearchHint" class="py-2 px-2 text-xs text-fontcolor-soft border-t border-dashed">{{ asyncSearchHint }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <ns-field-description :field="field"></ns-field-description>
    </div>
</template>
<script lang="ts">
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { Popup } from '~/libraries/popup';

declare const nsExtraComponents: any;
declare const nsComponents: any;
declare const nsNotice: any;

export default {
    data: () => {
        return {
            searchField: '',
            showResults: false,
            subscription: null,
            searchSubscription: null,
            searchDebounce: null,
            searchRequestId: 0,
            asyncSearchLoading: false,
            asyncSearchRemaining: 0,
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

            const options   =   this.optionList.filter( option => option.value === this.field.value );

            if ( options.length > 0 ) {
                return options[0].label;
            }

            return __( 'Choose...' );
        },
        filtredOptions() {
            if ( this.isAsyncSearch ) {
                return this.optionList;
            }

            if ( this.searchField.length > 0 ) {
                const search = this.searchField.toLocaleLowerCase();

                return this.field.options.filter( option => option.label.toLocaleLowerCase().includes( search ) ).splice(0,10);
            } else {
                return this.field.options;
            }
        },
        isAsyncSearch() {
            return !! this.searchConfig;
        },
        searchConfig() {
            return Array.isArray( this.field.options ) ? null : this.field.options?.search || null;
        },
        optionList() {
            if ( ! this.isAsyncSearch ) {
                return this.field.options || [];
            }

            return [
                ...( this.searchConfig.staticOptions || [] ),
                ...( this.searchConfig.options || [] ),
            ];
        },
        showAsyncSearchHint() {
            return this.isAsyncSearch && ! this.asyncSearchLoading && this.asyncSearchRemaining > 0;
        },
        asyncSearchHint() {
            const label = this.searchConfig.moreLabel || __( '+{count} more searchable options. Type to search.' );

            return label.replace( '{count}', this.asyncSearchRemaining );
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
        const options   =   this.optionList.filter( op => op.value === this.field.value );

        if ( this.isAsyncSearch ) {
            this.loadAsyncOptions( '', true );
        }

        if ( options.length > 0 && [ null, undefined ].includes( this.field.value ) ) {
            this.selectOption( options[0] );
        }

        /**
         * if the field provide a "subject" object, this means
         * it's likely to automatically refresh in case other field value change
         * only if the "refresh" property is provided to the watching field
         */
        if ( this.field.subject ) {
            this.subscription = this.field.subject.subscribe( ({ field, fields }) => {
                if ( field && fields && this.field.refresh && field.name === this.field.refresh.watch ) {
                    const url =  this.field.refresh.url;
                    const data = this.field.refresh.data;
                    const form = { identifier: field.value, ...data };

                    nsHttpClient.post( url, form ).subscribe({
                        next: options => {
                            this.field.options    =   options;
                        },
                        error: error => {
                            console.error( error );
                        }
                    });
                }
            });
        }

        document.addEventListener( 'click', ( event ) => {
            if ( this.$el.contains( event.target ) === false ) {
                this.showResults    =   false;
            }
        });
    },
    destroyed() {
        if ( this.subscription ) {
            this.subscription.unsubscribe();
        }

        if ( this.searchSubscription ) {
            this.searchSubscription.unsubscribe();
        }

        if ( this.searchDebounce ) {
            clearTimeout( this.searchDebounce );
        }
    },
    methods: { 
        __,
        handleSearchInput() {
            if ( ! this.isAsyncSearch ) {
                return;
            }

            if ( this.searchDebounce ) {
                clearTimeout( this.searchDebounce );
            }

            const minLength = Number( this.searchConfig.minLength || 0 );

            if ( this.searchField.length > 0 && this.searchField.length < minLength ) {
                return;
            }

            this.searchDebounce = setTimeout( () => {
                this.loadAsyncOptions( this.searchField );
            }, 250 );
        },
        loadAsyncOptions( search = '', includeSelected = false ) {
            const config = this.searchConfig;

            if ( ! config || ! config.identifier ) {
                return;
            }

            if ( this.searchSubscription ) {
                this.searchSubscription.unsubscribe();
            }

            const requestId = ++this.searchRequestId;
            const query = new URLSearchParams({
                search,
                page: '1',
                per_page: String( config.limit || 10 ),
            });

            Object.entries( config.query || {} ).forEach( ([ key, value ]) => {
                if ( value !== undefined && value !== null && value !== '' ) {
                    query.set( key, String( value ) );
                }
            });

            const url = `/api/crud/${encodeURIComponent( config.identifier )}?${query.toString()}`;
            this.asyncSearchLoading = true;
            this.searchSubscription = nsHttpClient.get( url ).subscribe({
                next: result => {
                    if ( requestId !== this.searchRequestId ) {
                        return;
                    }

                    config.options = ( result.data || [] ).map( entry => this.mapSearchEntry( entry, config ) );
                    this.asyncSearchRemaining = Math.max( 0, Number( result.total || 0 ) - config.options.length );
                    this.asyncSearchLoading = false;

                    if ( includeSelected && this.field.value !== undefined && this.field.value !== null && this.field.value !== '' && ! config.options.some( option => option.value === this.field.value ) ) {
                        this.loadSelectedOption( config, requestId );
                    }
                },
                error: error => {
                    if ( requestId !== this.searchRequestId ) {
                        return;
                    }

                    this.asyncSearchLoading = false;
                    this.asyncSearchRemaining = 0;
                    nsSnackBar.error( error.message || __( 'Unable to search options.' ) );
                },
            });
        },
        loadSelectedOption( config, requestId ) {
            nsHttpClient.get( `/api/crud/${encodeURIComponent( config.identifier )}/${encodeURIComponent( this.field.value ) }` ).subscribe({
                next: entry => {
                    if ( requestId !== this.searchRequestId || config.options.some( option => option.value === this.field.value ) ) {
                        return;
                    }

                    config.options.unshift( this.mapSearchEntry( entry, config ) );
                },
            });
        },
        mapSearchEntry( entry, config ) {
            const attributes = config.optionAttributes || this.field.props?.optionAttributes || { label: 'name', value: 'id' };

            return {
                label: entry[ attributes.label ],
                value: entry[ attributes.value ],
            };
        },
        hasSelectedValues( field ) {
            const values    =   this.optionList.map( option => option.value );

            return values.includes( field.value );
        },
        resetSelectedInput( field ) {
            if ( field.disabled ) {
                return;
            }
            
            field.value = null;
            this.$emit( 'change', null );
            this.showResults    =   false;
            this.searchField    =   '';
        },
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
                    nsSnackBar.error( __( `The component ${field.component} cannot be loaded. Make sure it's injected on nsExtraComponents object.` ) );
                }

                const result = await new Promise( ( resolve, reject ) => {
                    const response  =   Popup.show( component, { ...( field.props || {}), field: this.field, resolve, reject } );
                });

                this.$emit( 'saved', result );
            } catch ( error ) {
                console.log({ error })
                // probably the popup is closed
            }
        },
        triggerFieldAbout( field ) {
            nsNotice.info( __( 'About this field' ), field.about, {
                duration: 5000
            });
        }
    },
}
</script>
