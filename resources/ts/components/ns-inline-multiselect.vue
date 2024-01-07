<template>
    <div class="flex flex-col mb-2 flex-auto ns-input">
        <label v-if="field.label && ( field.label.length > 0)" :for="field.name" :class="hasError ? 'has-error' : 'is-pristine'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="( hasError ? 'has-error' : 'is-pristine' ) + ` ` + ( field.description || field.errors > 0 ? 'mb-2' : ''  )" class="mt-1 relative border-2 rounded-md focus:shadow-sm">
            <div v-if="leading" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="leading sm:text-sm sm:leading-5">
                {{ leading }}
                </span>
            </div>
            <div 
                :disabled="field.disabled" 
                :id="field.name" :type="field.type" 
                :class="inputClass" class="flex sm:text-sm sm:leading-5 p-1 flex-wrap" :placeholder="field.placeholder || ''">
                <div v-for="option of field.value" class="rounded shadow bg-box-elevation-hover flex mr-1 mb-1 ">
                    <div class="p-2 flex items-center text-primary">{{ optionsToKeyValue[ option ] }}</div>
                    <div class="flex items-center justify-center px-2">
                        <div @click="removeOption( option )" class="cursor-pointer rounded-full bg-error-tertiary h-5 w-5 flex items-center justify-center">
                            <i class="las la-times-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <input 
                        @change="( event ) => event.stopPropagation()" 
                        @keydown.enter="addOption()"  
                        ref="searchField" 
                        v-model="searchField" 
                        type="text" 
                        class="w-auto p-2 border-b border-dashed bg-transparent" 
                        :placeholder="field.placeholder || 'Start searching here...'">
                    <div class="h-0 absolute w-full z-10">
                        <div class="shadow bg-box-background absoluve bottom-0 w-full max-h-80 overflow-y-auto">
                            <ul>
                                <li @click="addOption( suggestion )" v-for="suggestion of optionSuggestions" class="p-2 hover:bg-box-elevation-hover text-primary cursor-pointer">{{ suggestion.label }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ns-field-description :field="field"></ns-field-description>
    </div>
</template>
<script>
export default {
    name: 'ns-input-label',
    props: [ 'field' ],
    data() {
        return {
            tags: [],
            searchField: '',
            focused: false,
            optionsToKeyValue: {}
        }
    },
    methods: {
        addOption( option ) {
            let optionToAdd;

            if ( this.optionSuggestions.length === 1 && option === undefined ) {
                optionToAdd     =   this.optionSuggestions[0];
            } else if ( option !== undefined ) {
                optionToAdd     =   option;
            }

            if ( optionToAdd !== undefined ) {
                const isAlreadyAdded    =   this.field.value.filter( optionValue => optionValue === optionToAdd.value ).length > 0;

                if ( ! isAlreadyAdded ) {
                    this.searchField    =   '';
                    this.field.value.push( optionToAdd.value );
                    this.$emit( 'change', this.field );
                }
            }
        },
        removeOption( optionToRemove ) {
            const newOptions    =   this.field.value.filter( optionValue => optionValue !== optionToRemove );
            this.field.value    =   newOptions;
        }
    },
    mounted() {
        this.$refs.searchField.addEventListener( 'focus', ( event ) => {
            this.focused    =   true;
        })
        this.$refs.searchField.addEventListener( 'blur', ( event ) => {
            setTimeout( () => {
                this.focused    =   false;
            }, 200 );
        })

        if ( this.field.value.length === undefined ) {
            // it should be avalid JSON otherwise it's an array
            try {
                this.field.value    =   JSON.parse( this.field.value );
            } catch( exception ) {
                this.field.value    =   [];
            }
        }
        
        // save options to keyvalue for a quick access
        this.field.options.forEach( option => {
            this.optionsToKeyValue[ option.value ]  =   option.label;
        })
    },
    computed: {
        hasError() {
            if ( this.field.errors !== undefined && this.field.errors.length > 0 ) {
                return true;
            }
            return false;
        },
        optionSuggestions() {
            // the options that are already
            // added shouldn't be listed again.
            if ( typeof this.field.value.map === 'function' ) {
                const alreadyAdded  =   this.field.value.map( option => option.value );
                return this.field.options.filter( option => {
                    return ! alreadyAdded.includes( option.value ) && this.focused > 0 && ( option.label.search( this.searchField ) > -1 || option.value.search( this.searchField ) > -1 );
                })
            }

            return [];
        },
        disabledClass() {
            return this.field.disabled ? 'ns-disabled cursor-not-allowed' : '';
        },
        inputClass() {
            return this.disabledClass + ' ' + this.leadClass
        },
        leadClass() {
            return this.leading ? 'pl-8' : ''; // px-4
        }
    },
    props: [ 'placeholder', 'leading', 'type', 'field' ],
}
</script>