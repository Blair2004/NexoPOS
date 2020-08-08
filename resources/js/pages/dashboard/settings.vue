<template>
    <div id="tabbed-card" v-if="form">
        <div id="card-header" class="flex flex-wrap">
            <div :class="tab.active ? 'bg-white' : 'bg-gray-300'" @click="setActive( tab )" v-bind:key="key" v-for="( tab, key ) of form.tabs" class="cursor-pointer px-4 py-2 rounded-tl-lg rounded-tr-lg">
                {{ tab.label }}
            </div>
        </div>
        <div class="card-body bg-white rounded-br-lg rounded-bl-lg shadow">
            <div class="-mx-4 flex flex-wrap p-2">
                <div class="w-full px-4 md:w-1/2 lg:w-1/3" v-bind:key="index" v-for="( field, index ) of activeTab.fields">
                    <div class="flex flex-col my-2">
                        <ns-field :field="field"></ns-field>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-400 p-2 flex justify-end">
                <ns-button type="info"><slot name="submit-button">Save Settings</slot></ns-button>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient } from '../../bootstrap';
import FormValidation from '../../libraries/form-validation';

export default {
    name: 'ns-settings',
    props: [ 'url' ],
    data() {
        return {
            validation: new FormValidation,
            form: []
        }
    },
    computed: {
        activeTab() {
            for( let tab in this.form.tabs ) {
                if ( this.form.tabs[ tab ].active === true ) {
                    return this.form.tabs[ tab ];
                }
            }
        }
    },
    mounted() {
        this.loadSettingsForm();
    },
    methods: {
        setActive( tab ) {
            for( let tab in this.form.tabs ) {
                this.form.tabs[ tab ].active     =   false;
            }
            tab.active  =   true;
        },
        loadSettingsForm() {
            nsHttpClient.get( this.url ).subscribe( form => {
                let i   =   0;
                for( let tab in form.tabs ) {
                    form.tabs[ tab ].active  =   false;
                    if ( i === 0 ) {
                        form.tabs[ tab ].active  =   true;
                    }
                    i++;
                }
                this.form  =    form;
            })
        }
    }
}
</script>