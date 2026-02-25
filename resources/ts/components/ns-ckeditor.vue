<script>
import ckeditor from '@ckeditor/ckeditor5-vue';
import {
	ClassicEditor,
	Essentials,
	Autoformat,
	Bold,
	Italic,
	BlockQuote,
	Heading,
	Image,
	ImageCaption,
	ImageStyle,
	ImageToolbar,
	PictureEditing,
	Indent,
	Link,
	List,
	Paragraph,
	PasteFromOffice,
	Table,
	TableToolbar,
	TextTransformation,
	CloudServices,
} from 'ckeditor5';

import 'ckeditor5/ckeditor5.css';
import { __ } from '~/libraries/lang';

class Editor extends ClassicEditor {
	static builtinPlugins = [
		Essentials,
		Autoformat,
		Bold,
		Italic,
		BlockQuote,
		Heading,
		Image,
		ImageCaption,
		ImageStyle,
		ImageToolbar,
		Indent,
		Link,
		List,
		Paragraph,
		PasteFromOffice,
		PictureEditing,
		Table,
		TableToolbar,
		TextTransformation,
		CloudServices
	];

	static defaultConfig = {
		licenseKey: 'GPL',
		toolbar: {
			items: [
				'undo', 'redo',
				'|', 'heading',
				'|', 'bold', 'italic',
				'|', 'link', 'uploadImage', 'insertTable', 'blockQuote', 'mediaEmbed',
				'|', 'bulletedList', 'numberedList', 'outdent', 'indent'
			]
		},
		image: {
			toolbar: [
				'imageStyle:inline',
				'imageStyle:block',
				'imageStyle:side',
				'|',
				'toggleImageCaption',
				'imageTextAlternative'
			]
		},
		table: {
			contentToolbar: [
				'tableColumn',
				'tableRow',
				'mergeTableCells'
			]
		},
		language: 'en'
	};
}

export default {
    data: () => {
        return {
            editor: Editor
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
    <div class="flex flex-col mb-2 flex-auto ns-ckeditor overflow-auto" :class="hasError ? 'has-error' : 'is-pristine'">
        <label :for="field.name" class="block leading-5 font-medium"><slot></slot></label>
        <div class="mt-1 relative rounded-md focus:shadow-sm mb-1">
            <div v-if="leading" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-fontcolor-soft sm:text-sm sm:leading-5">
                {{ leading }}
                </span>
            </div>
            <ckeditor class="w-[2rem]" :editor="editor" v-model="field.value"></ckeditor>
        </div>
        <ns-field-description :field="field"></ns-field-description>
    </div>
</template>