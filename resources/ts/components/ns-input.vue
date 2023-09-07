<template>
    <div class="flex flex-col mb-2 flex-auto ns-input">
        <label v-if="field.label && ( field.label.length > 0)" :for="field.name" :class="hasError ? 'has-error' : 'is-pristine'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="( hasError ? 'has-error' : 'is-pristine' ) + ` ` + ( field.description || field.errors > 0 ? 'mb-2' : ''  )" class="mt-1 relative overflow-hidden border-2 rounded-md focus:shadow-sm">
            <div v-if="leading" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="leading sm:text-sm sm:leading-5">
                {{ leading }}
                </span>
            </div>
            <input 
                :disabled="field.disabled" 
                v-model="field.value" 
                :id="field.name" :type="type || field.type || 'text'" 
                :class="inputClass" class="block w-full sm:text-sm sm:leading-5 h-10" :placeholder="field.placeholder || ''" />
        </div>
        <ns-field-description :field="field"></ns-field-description>
    </div>
</template>
<script>
export default {
    data: () => {
        return {
        }
    },
    mounted() {
        // ...
    },
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
    props: [ 'placeholder', 'leading', 'type', 'field' ],
}
</script>