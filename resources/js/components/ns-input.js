const { Vue }       =   require( '../bootstrap' );
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
        }
    },
    props: [ 'placeholder', 'leading', 'type', 'field' ],
    template: `
    <div class="flex flex-col mb-2">
        <label :for="field.name" :class="hasError ? 'text-red-700' : 'text-gray-700'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'border-red-400' : 'border-gray-200'" class="mt-1 relative border-2 rounded-md focus:shadow-sm">
            <div v-if="leading" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm sm:leading-5">
                {{ leading }}
                </span>
            </div>
            <input :readonly="field.disabled" v-model="field.value" @blur="$emit( 'blur', this )" @change="$emit( 'change', this )" :id="field.name" :type="type || 'text'" :class="leading ? 'pl-8' : 'px-4'" class="form-input bg-transparent block w-full pr-12 sm:text-sm sm:leading-5 h-10" :placeholder="placeholder" />
        </div>
        <p v-if="field.errors.length === 0" class="text-xs text-gray-500"><slot name="description"></slot></p>
        <p v-for="error of field.errors" class="text-xs text-red-400">
            <slot v-if="error.identifier === 'required'" :name="error.identifier">This field is required.</slot>
            <slot v-if="error.identifier === 'email'" :name="error.identifier">This field must contain a valid email address.</slot>
        </p>
    </div>
    `,
});

module.exports     =   nsInput;