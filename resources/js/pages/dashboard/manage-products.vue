
<template>
    <div class="form flex-auto flex flex-col" id="crud-form">
        <div v-if="Object.values( form ).length === 0" class="flex items-center justify-center flex-auto">
            <ns-spinner/>
        </div>
        <template v-if="Object.values( form ).length > 0">
            <div class="flex flex-col">
                <div class="flex justify-between items-center">
                    <label for="title" class="font-bold my-2 text-gray-700"><slot name="title">No title Provided</slot></label>
                    <div for="title" class="text-sm my-2 text-gray-700">
                        <a v-if="returnLink" :href="returnLink" class="rounded-full border border-gray-400 hover:bg-red-600 hover:text-white bg-white px-2 py-1">Return</a>
                    </div>
                </div>
                <div :class="form.main.disabled ? 'border-gray-500' : form.main.errors.length > 0 ? 'border-red-600' : 'border-blue-500'" class="flex border-2 rounded overflow-hidden">
                    <input v-model="form.main.value" 
                        @blur="formValidation.checkField( form.main )" 
                        @change="formValidation.checkField( form.main )" 
                        :disabled="form.main.disabled"
                        type="text" 
                        :class="form.main.disabled ? 'bg-gray-400' : ''"
                        class="flex-auto text-gray-700 outline-none h-10 px-2">
                    <button :disabled="form.main.disabled" :class="form.main.disabled ? 'bg-gray-500' : form.main.errors.length > 0 ? 'bg-red-500' : 'bg-blue-500'" @click="submit()" class="outline-none px-4 h-10 text-white border-l border-gray-400"><slot name="save">Save</slot></button>
                </div>
                <p class="text-xs text-gray-600 py-1" v-if="form.main.description && form.main.errors.length === 0">{{ form.main.description }}</p>
                <p class="text-xs py-1 text-red-500" v-bind:key="index" v-for="(error, index) of form.main.errors">
                    <span><slot name="error-required">{{ error.identifier }}</slot></span>
                </p>
            </div>
            <div id="form-container" class="-mx-4 flex flex-wrap mt-4">
                <div class="px-4 w-full md:w-1/2">
                    <div class="rounded bg-white shadow p-2" v-bind:key="index" v-for="( tab, index) of generalTab">
                        <ns-field v-bind:key="index" v-for="( field, index ) of tab.fields" :field="field"></ns-field>
                    </div>
                </div>
                <div class="px-4 w-full md:w-1/2">
                    <div id="tabbed-card">
                        <div id="card-header" class="flex flex-wrap">
                            <div @click="setTabActive( tab )" :class="tab.active ? 'bg-white' : 'bg-gray-100'" v-for="( tab, index ) of validTabs" v-bind:key="index" class="cursor-pointer px-4 py-2 rounded-tl-lg rounded-tr-lg">
                                {{ tab.label }}
                            </div>
                        </div>
                        <div class="card-body bg-white rounded-br-lg rounded-bl-lg shadow p-2">
                            <div class="flex flex-col" v-bind:key="index" v-for="( field, index ) of activeValidTab.fields">
                                <label for="" class="font-medium text-gray-700 pb-1">{{ field.label }}</label>
                                <ns-field  :field="field"></ns-field>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
<script>
import FormValidation from '../../libraries/form-validation'
import { nsSnackBar, nsHttpClient } from '../../bootstrap';

export default {
    data: () => {
        return {
            formValidation: new FormValidation,
            nsSnackBar,
            nsHttpClient,
        }
    },
    methods: {
        setTabActive( tab ) {
            this.validTabs.forEach( tab => tab.active = false );
            tab.active  =   true;
        },
        parseForm( form ) {
            form.main.value     =   form.main.value === undefined ? '' : form.main.value;
            form.main           =   this.formValidation.createFields([ form.main ])[0];
            let index           =   0;

            for( let key in form.tabs ) {
                if ( index === 1 && form.tabs[ key ].active === undefined ) {
                    form.tabs[ key ].active  =   true;
                }

                form.tabs[ key ].active     =   form.tabs[ key ].active === undefined ? false : form.tabs[ key ].active;
                form.tabs[ key ].fields     =   this.formValidation.createFields( form.tabs[ key ].fields );

                index++;
            }

            return form;
        },
    },
    name: 'ns-manage-products',
}
</script>