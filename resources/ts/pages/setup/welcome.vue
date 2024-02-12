<template>
    <div class="w-full md:w-3/5 lg:w-3/5 self-center">
        <div class="bg-white rounded shadow my-2 overflow-hidden">
            <div class="welcome-box border-b border-gray-300 p-3 text-gray-700">
                <p class="text-gray-600 text-sm">{{ __( 'Thank you for using NexoPOS to manage your store. This installation wizard will help you running NexoPOS in no time.' ) }}</p>
                <br>
                <p class="text-gray-600 text-sm">{{ __( 'Choose your language to get started.' ) }}</p>
                <ns-select @change="updateSelectedLanguage()" :field="field"></ns-select>
                <div class="py-2">
                    <h2 class="text-xl font-body text-gray-700">{{ __( 'Remaining Steps' ) }}</h2>
                    <ul class="text-gray-600 text-sm">
                        <li><i class="las la-arrow-right"></i> {{ __( 'Database Configuration' ) }}</li>
                        <li><i class="las la-arrow-right"></i> {{ __( 'Application Configuration' ) }}</li>
                    </ul>
                </div>
            </div>
            <div class="bg-gray-200 p-3 flex justify-end">
                <router-link to="/database" class="ns-button info">
                    <button type="button" class="rounded cursor-pointer py-2 px-3 font-semibold">
                        <i class="las la-database"></i> {{ __( 'Database Configuration' ) }}
                    </button>
                </router-link>
            </div>
        </div>
    </div>
</template>

<script lang="ts">

declare const nsLang, nsLanguages;

import { __ } from "~/libraries/lang";
export default {
    data() {
        return {
            field: {
                label: __( 'Language Selection' ),
                description: __( 'Select what will be the default language of NexoPOS.' ),
                name: 'language',
                value: nsLang || 'en', // default language
                options: Object.values( nsLanguages ).map( (value, index) => {
                    return {
                        value: Object.keys( nsLanguages )[ index ],
                        label: value
                    }
                })
            }
        }
    },
    methods: {
        __,
        updateSelectedLanguage(  ) {
            document.location   =   `?lang=${this.field.value}`
        }
    },
    mounted() {
        // ...
    }
}
</script>

<style>

</style>
