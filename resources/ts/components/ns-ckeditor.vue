<script>
import ckeditor from '@ckeditor/ckeditor5-vue';
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import { __ } from '~/libraries/lang';

export default {
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
}
</script>
<template>
    <div class="flex flex-col mb-2 flex-auto">
        <label :for="field.name" :class="hasError ? 'text-error-primary' : 'text-primary'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'has-error' : 'is-pristine'" class="mt-1 relative rounded-md focus:shadow-sm mb-1">
            <div v-if="leading" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-secondary sm:text-sm sm:leading-5">
                {{ leading }}
                </span>
            </div>
            <ckeditor class="w-full" :editor="editor" v-model="field.value"></ckeditor>
        </div>
        <ns-field-description :field="field"></ns-field-description>
    </div>
</template>