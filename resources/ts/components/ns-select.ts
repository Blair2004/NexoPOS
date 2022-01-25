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
            return this.field.disabled ? 'bg-gray-200 cursor-not-allowed' : 'bg-transparent';
        },
        inputClass() {
            return this.disabledClass + ' ' + this.leadClass
        },
        leadClass() {
            return this.leading ? 'pl-8' : 'px-4';
        }
    },
    methods: { __ },
    template: `
    <div class="flex flex-col flex-auto">
        <label :for="field.name" :class="hasError ? 'text-red-700 dark:text-red-500' : 'text-gray-700 dark:text-slate-300'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'border-red-400 dark:border-red-500' : 'border-gray-200 dark:border-slate-600'" class="border-2 mt-1 relative rounded-md shadow-sm mb-2">
            <select :disabled="field.disabled ? field.disabled : false" @change="$emit( 'change', $event )" :name="field.name" v-model="field.value" :class="inputClass" class="text-gray-700 dark:text-slate-300 form-input block w-full pl-7 pr-12 sm:text-sm sm:leading-5 h-10 appearance-none">
                <option :value="option.value" v-for="option of field.options" class="py-2">{{ option.label }}</option>
            </select>
        </div>
        <p v-if="! field.errors || field.errors.length === 0" class="text-xs text-gray-500 dark:text-slate-300"><slot name="description"></slot></p>
        <p v-for="error of field.errors" class="text-xs text-red-500">
            <slot v-if="error.identifier === 'required'" :name="error.identifier">{{ __( 'This field is required.' ) }}</slot>
            <slot v-if="error.identifier === 'email'" :name="error.identifier">{{ __( 'This field must contain a valid email address.' ) }}</slot>
            <slot v-if="error.identifier === 'invalid'" :name="error.identifier">{{ error.message }}</slot>
        </p>
    </div>
    `,
});

export { nsSelect }