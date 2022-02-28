import Vue from 'vue';
const nsInput      =   Vue.component( 'ns-input', {
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
            return this.leading ? 'pl-8' : 'px-4';
        }
    },
    props: [ 'placeholder', 'leading', 'type', 'field' ],
    template: `
    <div class="flex flex-col mb-2 flex-auto ns-input">
        <label :for="field.name" :class="hasError ? 'has-error' : 'is-pristine'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'has-error' : 'is-pristine'" class="mt-1 relative overflow-hidden border-2 rounded-md focus:shadow-sm mb-2">
            <div v-if="leading" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="leading sm:text-sm sm:leading-5">
                {{ leading }}
                </span>
            </div>
            <input 
                :disabled="field.disabled" 
                v-model="field.value" 
                @blur="$emit( 'blur', this )" 
                @change="$emit( 'change', this )" 
                :id="field.name" :type="type || field.type || 'text'" 
                :class="inputClass" class="block w-full sm:text-sm sm:leading-5 h-10" :placeholder="placeholder" />
        </div>
        <p v-if="! field.errors || field.errors.length === 0" class="text-xs ns-description"><slot name="description"></slot></p>
        <p v-for="error of field.errors" class="text-xs ns-error">
            <slot v-if="error.identifier === 'required'" :name="error.identifier">This field is required.</slot>
            <slot v-if="error.identifier === 'email'" :name="error.identifier">This field must contain a valid email address.</slot>
            <slot v-if="error.identifier === 'invalid'" :name="error.identifier">{{ error.message }}</slot>
        </p>
    </div>
    `,
});

export { nsInput }