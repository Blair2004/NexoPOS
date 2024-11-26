<script>
export default {
    data: () => {
        return {
        }
    },
    name: 'ns-switch',
    emits: [ 'change', 'blur' ],
    mounted() {
    },
    computed: {
        _options() {
            return this.field.options.map( option => {
                option.selected     =   option.value    === this.field.value;
                return option;
            })
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
        sizeClass() {
            return ` w-1/${this._options.length <= 4 ? this._options.length : 4}`;
        },
        inputClass() {
            return this.disabledClass + ' ' + this.leadClass;
        },
        leadClass() {
            return this.leading ? 'pl-8' : 'px-4';
        }
    },
    methods: {
        __,
        setSelected( option ) {
            this.field.value    =   option.value;
            this._options.forEach( option => option.selected = false );
            option.selected     =   true;
            this.$forceUpdate();
            this.$emit( 'change', option.value );
        }
    },
    props: [ 'placeholder', 'leading', 'type', 'field' ],
}
</script>
<template>
    <div class="mb-2 ns-switch">
        <label :for="field.name" :class="hasError ? 'has-error' : 'is-pristine'" class="block leading-5 font-medium"><slot></slot></label>
            <div class="rounded-lg flex w-52 overflow-hidden shadow my-1" :class="hasError ? 'has-error' : ''">
                <button 
                    :disabled="option.disabled" 
                    :key="key"
                    v-for="(option, key) of _options" 
                    @click="setSelected( option )" 
                    :class="option.selected ? 'selected ' + sizeClass : 'unselected' + ' ' + inputClass + ' ' + sizeClass" 
                    class="p-2 text-sm flex-no-wrap outline-none rounded-none">{{ option.label }}</button>
            </div>
            <p v-if="! field.errors || field.errors.length === 0" class="text-xs ns-description"><slot name="description"></slot></p>
            <p v-for="(error, index) of field.errors" :key="index" class="text-xs ns-error">
                <slot v-if="error.identifier === 'required'" :name="error.identifier">{{ __( 'This field is required.' ) }}</slot>
            </p>
        </div>
</template>