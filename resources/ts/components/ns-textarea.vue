<template>
    <div class="flex flex-col mb-2 flex-auto ns-textarea">
        <label :for="field.name" :class="hasError ? 'text-error-primary' : 'text-primary'" class="block leading-5 font-medium">{{ field.label }}</label>
        <div :class="hasError ? 'has-error' : 'is-pristine'" class="mt-1 relative border-2 overflow-hidden rounded-md focus:shadow-sm mb-1">
            <div v-if="leading" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-secondary sm:text-sm sm:leading-5">
                {{ leading }}
                </span>
            </div>
            <textarea 
                :rows="field.data ? ( field.data.rows || 10 ) : 10"
                :disabled="field.disabled" 
                v-model="field.value" 
                @blur="$emit( 'blur', this )" 
                @change="$emit( 'change', this )" 
                :id="field.name" :type="type || field.type || 'text'" 
                :class="inputClass" class="form-input block w-full sm:text-sm sm:leading-5" :placeholder="placeholder"></textarea>
        </div>
        <p v-if="! field.errors || field.errors.length === 0" class="text-xs text-secondary"><slot name="description"></slot></p>
        <p :key="index" v-for="(error,index) of field.errors" class="text-xs text-error-primary">
            <slot v-if="error.identifier === 'required'" :name="error.identifier">This field is required.</slot>
            <slot v-if="error.identifier === 'email'" :name="error.identifier">This field must contain a valid email address.</slot>
            <slot v-if="error.identifier === 'invalid'" :name="error.identifier">{{ error.message }}</slot>
        </p>
    </div>
</template>
<script>
export default {
    data: () => {
        return {
        }
    },
    mounted() {
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
            return this.leading ? 'p-8' : 'p-2';
        }
    },
    props: [ 'placeholder', 'leading', 'type', 'field' ],
}
</script>