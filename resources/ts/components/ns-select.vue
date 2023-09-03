<template>
    <div class="flex flex-col flex-auto ns-select">
        <label :for="field.name" :class="hasError ? 'has-error' : 'is-pristine'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'has-error' : 'is-pristine'" class="border-2 mt-1 relative rounded-md shadow-sm mb-2 overflow-hidden">
            <select 
                :disabled="field.disabled ? field.disabled : false" 
                :name="field.name" v-model="field.value" 
                :class="inputClass" 
                class="form-input block w-full pl-7 pr-12 sm:text-sm sm:leading-5 h-10 appearance-none">
                <option :value="null">{{ __( 'Choose an option' ) }}</option>
                <option :key="index" :value="option.value" v-for="(option,index) of field.options" class="py-2">{{ option.label }}</option>
            </select>
        </div>
        <p v-if="! field.errors || field.errors.length === 0" class="text-xs ns-description">{{ field.description }}</p>
        <p :key="index" v-for="(error,index) of field.errors" class="text-xs ns-error">
            <slot v-if="error.identifier === 'required'" :name="error.identifier">{{ __( 'This field is required.' ) }}</slot>
            <slot v-if="error.identifier === 'email'" :name="error.identifier">{{ __( 'This field must contain a valid email address.' ) }}</slot>
            <slot v-if="error.identifier === 'invalid'" :name="error.identifier">{{ error.message }}</slot>
        </p>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';
export default {
    data: () => {
        return {
        }
    },
    props: [ 'name', 'placeholder', 'field', 'leading' ],
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
        // ...
    },
    methods: { __ },
}
</script>