<template>
    <div id="tabbed-card" class="ns-tab" v-if="formDefined">
        <div id="card-header" class="flex flex-wrap ml-4">
            <div 
                :class="tab.active ? 'active' : 'inactive'" 
                v-for="( tab, key ) of form.tabs" 
                @click="setActive( tab, key )" v-bind:key="key" 
                class="tab cursor-pointer flex items-center px-4 py-2 rounded-tl-lg rounded-tr-lg">
                <span>{{ tab.label }}</span>
                <span v-if="tab.errors && tab.errors.length > 0" class="ml-2 rounded-full ns-inset-button error active text-sm h-6 w-6 flex items-center justify-center">{{ tab.errors.length }}</span>
            </div>
        </div>
        <div class="card-body ns-tab-item">
            <div class="shadow rounded">
                <div class="-mx-4 flex flex-wrap p-2">
                    <template v-if="activeTab.fields">
                        <div class="w-full px-4 md:w-1/2 lg:w-1/3" v-bind:key="index" v-for="( field, index ) of activeTab.fields">
                            <div class="flex flex-col my-2">
                                <ns-field @saved="handleSaved( $event, field )" :field="field"></ns-field>
                            </div>
                        </div>
                    </template>
                    <div class="w-full px-4" v-if="activeTab.component">
                        <component v-bind:is="loadComponent( activeTab.component ).value"></component>
                    </div>
                </div>
                <div v-if="activeTab.fields && activeTab.fields.length > 0" class="ns-tab-item-footer border-t p-2 flex justify-end">
                    <ns-button :disabled="isSubmitting" @click="submitForm()" type="info"><slot name="submit-button">{{ __( 'Save Settings' ) }}</slot></ns-button>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { __ } from '~/libraries/lang';
import FormValidation from '~/libraries/form-validation';
import nsField from '~/components/ns-field.vue';
import { shallowRef } from '@vue/reactivity';

declare const nsExtraComponents, nsHooks, nsHttpClient, nsSnackBar;

export default {
    name: 'ns-settings',
    props: [ 'url' ],
    components: { nsField },
    data() {
        return {
            validation: new FormValidation,
            form: {},
            isSubmitting: false,
            test: '',
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
        },
        activeTabIdentifier() {
            const values        =   Object.values( this.form.tabs );
            const tabIdentifier =   Object.keys( this.form.tabs )[ values.indexOf( this.activeTab ) ];

            return tabIdentifier;
        },
    },
    mounted() {
        const address   =   window.location.href;
        const url       =   new URL( address );

        this.loadSettingsForm( url.searchParams.get( 'tab' ) || null );
    },
    methods: {
        __,
        /**
         * @todo Here we're reloading the settings once we have
         * a "saved" event dispatched by one of the fields. It might be an issue
         * if the user hasn't saved a settings yet and try to use a field that dispaches the "saved" event.
         * This will cause the form to reset. We might proceed with partial loading
         * 
         * @param event 
         * @param field 
         */
        async handleSaved( event, field ) {
            const form = await this.loadSettingsForm( this.activeTab );

            form.tabs[ this.activeTabIdentifier ].fields.filter( __field => {
                    if ( __field.name === field.name && event.data.entry ) {
                        __field.value = event.data.entry.id;
                    }
                })
        },
        loadComponent( componentName ) {
            return shallowRef( nsExtraComponents[ componentName ] );
        },
        async submitForm() {
            if ( this.validation.validateForm( this.form ).length > 0 ) {
                return nsSnackBar.error( __( 'Unable to proceed the form is not valid.' ) )
                    .subscribe();
            }
            
            this.validation.disableForm( this.form );
            const form  =   this.validation.extractForm( this.form );

            /**
             * This wil allow any external to hook into saving process
             * and prevent the regular process to run.
             */
            const beforeSaveHook    =   nsHooks.applyFilters( 'ns-before-saved', () => new Promise( ( resolve, reject ) => {
                this.isSubmitting   =   true;
                return nsHttpClient.post( this.url, form )
                    .subscribe({
                        next: result => {
                            this.isSubmitting   =   false;
                            resolve( result );
                        },
                        error: ( error ) => {
                            this.isSubmitting   =   false;
                            reject( error )
                        }
                    })
            } ) );

            try {
                const result    =   await beforeSaveHook( form );

                this.validation.enableForm( this.form );
                const values        =   Object.values( this.form.tabs );
                const tabIdentifier =   Object.keys( this.form.tabs )[ values.indexOf( this.activeTab ) ];
                this.loadSettingsForm( tabIdentifier );

                if ( result.data && result.data.results ) {
                    result.data.results.forEach( response => {
                        if ( response.status === 'error' ) {
                            nsSnackBar.error( response.message ).subscribe();
                        } else {
                            nsSnackBar.success( response.message ).subscribe();
                        }
                    });
                }

                nsHooks.doAction( 'ns-settings-saved', { result, instance: this });
                nsSnackBar.success( result.message ).subscribe();

            } catch( error ) {
                this.validation.enableForm( this.form );
                this.validation.triggerFieldsErrors( this.form, error );
                
                nsHooks.doAction( 'ns-settings-failed', { error, instance: this });

                if ( error.message ) {
                    nsSnackBar.error( error.message || __( 'Unable to proceed the form is not valid.' ) )
                        .subscribe();
                }
            }                
        },
        setActive( tab, identifier ) {
            for( let _tab in this.form.tabs ) {
                this.form.tabs[ _tab ].active     =   false;
            }

            tab.active  =   true;

            nsHooks.doAction( 'ns-settings-change-tab', { tab, instance: this, identifier });
        },
        loadSettingsForm( activeTab = null ) {
            return new Promise( ( resolve, reject ) => {
                nsHttpClient.get( this.url ).subscribe({
                    next: form => {

                        resolve( form );

                        let i   =   0;
                        let activeTabIdentifier    =   null;

                        /**
                         * This will force the settings page
                         * to refresh all the fields.
                         */
                        this.form   =   {};

                        /**
                         * if we provide a tab that doesn't exists
                         * then we'll make suer to set it as undefined.
                         * So the first tab will be used by default.
                         */
                        activeTab   =   form.tabs[ activeTab ] !== undefined ? activeTab : null;

                        /**
                         * only if it doesn't have selected
                         * tab so that we can reload it without resetting the focused tab.
                         */
                        for( let tab in form.tabs ) {
                            if ( ! this.formDefined ) {
                                form.tabs[ tab ].active  =   false;

                                if ( activeTab === null && i === 0 ) {
                                    form.tabs[ tab ].active  =   true;
                                    activeTabIdentifier      =   tab;
                                } else if ( activeTab !== null && tab === activeTab ) {
                                    form.tabs[ tab ].active  =   true;
                                    activeTabIdentifier      =   tab;
                                }
                            } else {
                                form.tabs[ tab ].active  =   this.form.tabs[ tab ].active;
                            }

                            i++;

                            /**
                             * to avoid unnecessary errors
                             * let's create empty fields if those
                             * aren't provided.
                             */
                            if ( form.tabs[ tab ].fields === undefined ) {
                                form.tabs[ tab ].fields     =   [];
                            }
                        }

                        this.form  =    this.validation.createForm( form );

                        nsHooks.doAction( 'ns-settings-loaded', this );
                        nsHooks.doAction( 'ns-settings-change-tab', { tab : this.activeTab, instance: this, identifier: activeTabIdentifier });
                    },
                    error : error => {
                        nsSnackBar.error( error.message ).subscribe();
                        reject( error );
                    }
                });
            })
        }
    }
}
</script>