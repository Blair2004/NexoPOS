import { Popup } from '@/libraries/popup';
import Vue from 'vue';
import { default as nsMedia } from "@/pages/dashboard/ns-media.vue";

const nsMediaInput   =   Vue.component( 'ns-media-input', {
    template: `
    <div class="flex flex-col mb-2 flex-auto ns-media">
        <label :for="field.name" :class="hasError ? 'has-error' : 'is-pristine'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'has-error' : 'is-pristine'" class="mt-1 relative border-2 rounded-md focus:shadow-sm">
            <div v-if="leading" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-primary sm:text-sm sm:leading-5">
                {{ leading }}
                </span>
            </div>
            <div class="rounded overflow-hidden flex">
                <div 
                    v-if="field.data && field.data.type === 'model'"
                    class="form-input flex w-full sm:text-sm items-center sm:leading-5 h-10">
                    <template v-if="field.value && field.value.name">
                        <img class="w-8 h-8 m-1" :src="field.value.sizes.thumb" :alt="field.value.name">
                        <span class="text-xs text-secondary">{{ field.value.name }}</span>
                    </template>
                </div>
                <input 
                    v-if="! field.data || field.data.type === 'undefined' || field.data.type === 'url'"
                    v-model="field.value" 
                    :disabled="field.disabled"
                    @blur="$emit( 'blur', this )" 
                    @change="$emit( 'change', this )" 
                    :id="field.name" :type="type || field.type || 'text'" 
                    :class="inputClass" class="form-input block w-full sm:text-sm sm:leading-5 h-10" :placeholder="placeholder" />
                <button 
                    @click="toggleMedia( field )"
                    class="w-10 h-10 flex items-center justify-center border-l-2 outline-none">
                    <i class="las la-photo-video"></i>
                </button>
            </div>
        </div>
        <p v-if="! field.errors || field.errors.length === 0" class="text-xs text-primary"><slot name="description"></slot></p>
        <p v-for="error of field.errors" class="text-xs text-error-primary">
            <slot v-if="error.identifier === 'required'" :name="error.identifier">{{ __( 'This field is required.' ) }}</slot>
            <slot v-if="error.identifier === 'email'" :name="error.identifier">{{ __( 'This field must contain a valid email address.' ) }}</slot>
            <slot v-if="error.identifier === 'invalid'" :name="error.identifier">{{ error.message }}</slot>
        </p>
    </div>
    `,
    computed: {
        hasError() {
            if ( this.field.errors !== undefined && this.field.errors.length > 0 ) {
                return true;
            }
            return false;
        },
        disabledClass() {
            return this.field.disabled ? 'ns-disabled cursor-not-allowed' : 'ns-enabled';
        },
        inputClass() {
            return this.disabledClass + ' ' + this.leadClass
        },
        leadClass() {
            return this.leading ? 'pl-8' : 'px-4';
        }
    },
    data() {
        return {
            
        }
    },
    props: [ 'placeholder', 'leading', 'type', 'field' ],
    mounted() {
    },
    methods: {
        toggleMedia() {
            const promise   =   new Promise( ( resolve, reject ) => {
                Popup.show( nsMedia, { resolve, reject, ...( this.field.data || {} )});
            });

            promise.then( (action:any) => {
                /**
                 * When the performed action is "use-selected"
                 * we define the current field value with what has been selected (first entry)
                 * and we close the popup.
                 */
                if ( action.event === 'use-selected' ) {
                    /**
                     * a field might choose to use a URL 
                     * or to link to an existing model
                     */
                    if ( ( ! this.field.data || this.field.data.type === 'url' ) ) {
                        this.field.value    =   action.value[0].sizes.original;
                    } else if ( ( ! this.field.data || this.field.data.type === 'model' ) ) {
                        this.field.value    =   action.value[0];
                    }

                    this.$forceUpdate();
                }
            });
        }
    }
});

export { nsMediaInput };