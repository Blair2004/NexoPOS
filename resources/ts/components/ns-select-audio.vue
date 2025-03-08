
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
            return this.field.disabled ? 'bg-gray-200 cursor-not-allowed' : 'bg-transparent';
        },
        inputClass() {
            return this.disabledClass + ' ' + this.leadClass
        },
        leadClass() {
            return this.leading ? 'pl-8' : 'px-4';
        }
    },
    methods: { 
        __,
        playSelectedSound() {
            if ( this.field.value !== null && this.field.value.length > 0 ) {
                (new Audio( this.field.value )).play();
            }
        }
    },
}
</script>
<template>
    <div class="flex flex-col flex-auto ns-select-audio">
        <label :for="field.name" :class="hasError ? 'has-error' : 'is-pristine'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'error' : ''" class="mt-1 flex relative mb-1 form-input">
            <button @click="playSelectedSound()" class="rounded-l border w-12 flex-auto flex items-center justify-center">
                <i class="las la-play text-2xl"></i>
            </button>
            <select :disabled="field.disabled ? field.disabled : false" @change="$emit( 'change', $event )" :name="field.name" v-model="field.value" :class="inputClass" class="text-fontcolor block w-full pl-7 pr-12 sm:text-sm sm:leading-5 h-10 outline-hidden">
                <option :key="index" :value="option.value" v-for="(option,index) of field.options" class="py-2">{{ option.label }}</option>
            </select>
        </div>
        <ns-field-description :field="field"></ns-field-description>
    </div>
</template>