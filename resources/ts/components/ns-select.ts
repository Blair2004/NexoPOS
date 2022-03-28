import { __ } from '@/libraries/lang';
import Vue from 'vue';
const nsSelect      =   Vue.component( 'ns-select', {
    data: () => {
        return {
        }
    },
    props: [ 'name', 'placeholder', 'field' ],
    computed: {
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
    mounted() {
        console.log( this.field.value );
    },
    methods: { __ },
    template: `
    <div class="flex flex-col flex-auto ns-select">
        <label :for="field.name" :class="hasError ? 'has-error' : 'is-pristine'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'has-error' : 'is-pristine'" class="border-2 mt-1 relative rounded-md shadow-sm mb-2 overflow-hidden">
            <select :disabled="field.disabled ? field.disabled : false" @change="$emit( 'change', $event )" :name="field.name" v-model="field.value" :class="inputClass" class="form-input block w-full pl-7 pr-12 sm:text-sm sm:leading-5 h-10 appearance-none">
                <option v-if="field.value === undefined" :value="undefined">{{ __( 'Choose an option' ) }}</option>
                <option v-if="field.value === null" :value="null">{{ __( 'Choose an option' ) }}</option>
                <option :value="option.value" v-for="option of field.options" class="py-2">{{ option.label }}</option>
            </select>
        </div>
        <p v-if="! field.errors || field.errors.length === 0" class="text-xs ns-description"><slot name="description"></slot></p>
        <p v-for="error of field.errors" class="text-xs ns-error">
            <slot v-if="error.identifier === 'required'" :name="error.identifier">{{ __( 'This field is required.' ) }}</slot>
            <slot v-if="error.identifier === 'email'" :name="error.identifier">{{ __( 'This field must contain a valid email address.' ) }}</slot>
            <slot v-if="error.identifier === 'invalid'" :name="error.identifier">{{ error.message }}</slot>
        </p>
    </div>
    `,
});

export { nsSelect }