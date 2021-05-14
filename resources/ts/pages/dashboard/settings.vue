<template>
    <div id="tabbed-card" v-if="formDefined">
        <div id="card-header" class="flex flex-wrap">
            <div 
                :class="tab.active ? 'bg-white' : 'bg-gray-300'" 
                @click="setActive( tab )" v-bind:key="key" 
                v-for="( tab, key ) of form.tabs" 
                class="text-gray-700 cursor-pointer flex items-center px-4 py-2 rounded-tl-lg rounded-tr-lg">
                <span>{{ tab.label }}</span>
                <span v-if="tab.errors.length > 0" class="ml-2 rounded-full bg-red-400 text-white text-sm h-6 w-6 flex items-center justify-center">{{ tab.errors.length }}</span>
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
                <ns-button @click="submitForm()" type="info"><slot name="submit-button">{{ __( 'Save Settings' ) }}</slot></ns-button>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '@/libraries/lang';
import { nsHooks, nsHttpClient, nsSnackBar } from '../../bootstrap';
import FormValidation from '../../libraries/form-validation';

export default {
    name: 'ns-settings',
    props: [ 'url' ],
    data() {
        return {
            validation: new FormValidation,
            form: {},
            test: ''
        }
    },
    computed: {
        formDefined() {
            return Object.values( this.form ).length > 0;
        },
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
        __,
        submitForm() {
            if ( this.validation.validateForm( this.form ).length === 0 ) {
                this.validation.disableForm( this.form );
                return nsHttpClient.post( this.url, this.validation.extractForm( this.form ) )
                    .subscribe( result => {
                        this.validation.enableForm( this.form );
                        this.loadSettingsForm();

                        if ( result.data && result.data.results ) {
                            result.data.results.forEach( response => {
                                if ( response.status === 'failed' ) {
                                    nsSnackBar.error( response.message ).subscribe();
                                } else {
                                    nsSnackBar.success( response.message ).subscribe();
                                }
                            });
                        }

                        nsHooks.doAction( 'ns-settings-saved', { result, instance: this });
                        nsSnackBar.success( result.message ).subscribe();
                    }, ( error ) => {
                        this.validation.enableForm( this.form );
                        this.validation.triggerFieldsErrors( this.form, error );
                        
                        nsHooks.doAction( 'ns-settings-failed', { error, instance: this });

                        nsSnackBar.error( error.message || __( 'Unable to proceed the form is not valid.' ) )
                            .subscribe();
                    })
            }

            nsSnackBar.error( this.$slots[ 'error-form-invalid' ][0].text || __( 'Unable to proceed the form is not valid.' ) )
                .subscribe();
        },
        setActive( tab ) {
            for( let tab in this.form.tabs ) {
                this.form.tabs[ tab ].active     =   false;
            }
            tab.active  =   true;
            nsHooks.doAction( 'ns-settings-change-tab', { tab, instance: this });
        },
        loadSettingsForm() {
            nsHttpClient.get( this.url ).subscribe( form => {
                let i   =   0;
                const hasSelected   =   Object.values( form.tabs ).filter( t => t.active ).length > 0;

                /**
                 * only if it doesn't have selected
                 * tab so that we can reload it without resetting the focused tab.
                 */
                for( let tab in form.tabs ) {
                    if ( ! this.formDefined ) {
                        form.tabs[ tab ].active  =   false;
                        if ( i === 0 ) {
                            form.tabs[ tab ].active  =   true;
                        }
                    } else {
                        form.tabs[ tab ].active  =   this.form.tabs[ tab ].active;
                    }
                    i++;
                }

                this.form  =    this.validation.createForm( form );
                nsHooks.doAction( 'ns-settings-loaded', this );
            })
        }
    }
}
</script>