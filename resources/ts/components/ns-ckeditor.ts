import Vue from 'vue';
import ckeditor from '@ckeditor/ckeditor5-vue2';
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import { __ } from '@/libraries/lang';
const nsCkeditor    =   Vue.component( 'ns-ckeditor', {
    data: () => {
        return {
            editor: ClassicEditor
        }
    },
    components: {
        ckeditor : ckeditor.component
    },
    mounted() {
    },
    methods: { __ },
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
            return this.leading ? 'p-8' : 'p-2';
        }
    },
    props: [ 'placeholder', 'leading', 'type', 'field' ],
    template: `
    <div class="flex flex-col mb-2 flex-auto">
        <label :for="field.name" :class="hasError ? 'text-error-primary' : 'text-primary'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'has-error' : 'is-pristine'" class="mt-1 relative rounded-md focus:shadow-sm mb-2">
            <div v-if="leading" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-secondary sm:text-sm sm:leading-5">
                {{ leading }}
                </span>
            </div>
            <ckeditor class="w-full" :editor="editor" v-model="field.value"></ckeditor>
        </div>
        <p v-if="! field.errors || field.errors.length === 0" class="text-xs text-secondary"><slot name="description"></slot></p>
        <p v-for="error of field.errors" class="text-xs text-error-primary">
            <slot v-if="error.identifier === 'required'" :name="error.identifier">{{ __( 'This field is required.' ) }}</slot>
            <slot v-if="error.identifier === 'email'" :name="error.identifier">{{ __( 'This field must contain a valid email address.' ) }}</slot>
            <slot v-if="error.identifier === 'invalid'" :name="error.identifier">{{ error.message }}</slot>
        </p>
    </div>
    `,
});

export { nsCkeditor }