
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
    <div class="flex flex-col flex-auto">
        <label :for="field.name" :class="hasError ? 'text-error-primary' : 'text-primary'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'border-error-primary' : 'border-input-edge'" class="border-2 mt-1 flex relative overflow-hidden rounded-md shadow-sm mb-1 form-input">
            <div @click="playSelectedSound()" class="border-r-2 border-input-edge flex-auto flex items-center justify-center hover:bg-info-tertiary hover:text-white">
                <button class="w-10 flex item-center justify-center">
                    <i class="las la-play text-2xl"></i>
                </button>
            </div>
            <select :disabled="field.disabled ? field.disabled : false" @change="$emit( 'change', $event )" :name="field.name" v-model="field.value" :class="inputClass" class="text-primary block w-full pl-7 pr-12 sm:text-sm sm:leading-5 h-10 outline-none">
                <option :key="index" :value="option.value" v-for="(option,index) of field.options" class="py-2">{{ option.label }}</option>
            </select>
        </div>
        <ns-field-description :field="field"></ns-field-description>
    </div>
</template>