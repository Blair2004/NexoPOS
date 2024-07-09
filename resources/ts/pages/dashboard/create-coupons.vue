<script>
import FormValidation from '../../libraries/form-validation';
import { nsSnackBar, nsHttpClient } from '../../bootstrap';
import { __ } from '~/libraries/lang';
import popupResolver from '~/libraries/popup-resolver';
export default { 
    name: 'ns-create-coupons',
    mounted() {
        this.loadForm();
    },
    computed: {
        validTabs() {
            if ( this.form ) {
                const tabs  =   [];
                for( let tab in this.form.tabs ) {
                    if ([ 'selected_products', 'selected_categories', 'selected_groups', 'selected_customers' ].includes( tab ) ) {
                        tabs.push( this.form.tabs[ tab ] );
                    }
                }
                return tabs;
            }
            return [];
        },
        activeValidTab() {
            return this.validTabs.filter( tab => tab.active )[0];
        },
        generalTab() {
            const tabs  =   [];
            for( let tab in this.form.tabs ) {
                if ([ 'general' ].includes( tab ) ) {
                    tabs.push( this.form.tabs[ tab ] );
                }
            }
            return tabs;
        }
    },
    data() {
        return {
            formValidation: new FormValidation,
            form: {},
            labels: {},
            nsSnackBar,
            nsHttpClient,
        }
    },
    props: [ 'submitMethod', 'submitUrl', 'returnUrl', 'src', 'rules', 'popup' ],
    methods: {
        __,
        popupResolver,
        setTabActive( tab ) {
            this.validTabs.forEach( tab => tab.active = false );
            tab.active  =   true;
        },
        submit() {
            if ( this.formValidation.validateForm( this.form ).length > 0 ) {
                return nsSnackBar.error( __( 'Unable to proceed the form is not valid.'), __( 'Okay' ) )
                    .subscribe();
            }

            if ( this.submitUrl === undefined ) {
                return nsSnackBar.error( __( 'No submit URL was provided' ), __( 'Okay' ) )
                    .subscribe();
            }

            this.formValidation.disableForm( this.form );

            const data  =   {
                ...this.formValidation.extractForm( this.form ),
            }

            nsHttpClient[ this.submitMethod ? this.submitMethod.toLowerCase() : 'post' ]( this.submitUrl, data )
                .subscribe( data => {
                    if ( data.status === 'success' ) {
                        if ( this.popup ) {
                            this.popupResolver( data );
                        } else {
                            return document.location   =   this.returnUrl;
                        }
                    }
                    this.formValidation.enableForm( this.form );
                }, ( error ) => {
                    nsSnackBar.error( error.message || __( 'An unexpected error occurred.' ), undefined, {
                        duration: 5000
                    }).subscribe();

                    if ( error.status === 'error' ) {
                        this.formValidation.triggerError( this.form, error.data );
                    }

                    this.formValidation.enableForm( this.form );
                })
        },
        handleGlobalChange( event ) {
            this.globallyChecked    =   event;
            this.rows.forEach( r => r.$checked = event );
        },
        loadForm() {
            const request   =   nsHttpClient.get( `${this.src}` );
            request.subscribe( f => {
                this.labels     =   f.labels;
                this.form    =   this.parseForm( f.form );
            });
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
        addOption( option ) {
            const index     =   this.options.indexOf( option );

            if ( index >= 0 ) {
                this.options[ index ].selected  =   !this.options[ index ].selected;
            }
        },
        removeOption({ option, index }) {
            option.selected     =   false;
        },
        getRuleForm() {
            return this.form.ruleForm;
        },
        addRule() {
            this.form.rules.push( this.getRuleForm() );
        },
        removeRule( index ) {
            this.form.rules.splice( index, 1 );
        }
    }
}
</script>
<template>
    <div class="form flex-auto flex flex-col" id="crud-form">
        <div v-if="Object.values( form ).length === 0" class="flex items-center justify-center flex-auto">
            <ns-spinner/>
        </div>
        <template v-if="Object.values( form ).length > 0">
            <div :class="popup ? 'bg-box-background w-95vw md:w-2/3-screen h-3/4-screen overflow-y-auto p-4' : ''">
                <div class="flex flex-col">
                    <div class="flex justify-between items-center">
                        <label for="title" class="font-bold my-2 text-primary">{{ submitMethod.toLowerCase() === 'post' ? labels.create_title : labels.edit_title }}</label>
                        <div for="title" class="text-sm my-2" v-if="!popup">
                            <a v-if="returnUrl" :href="returnUrl" class="rounded-full border ns-inset-button error px-2 py-1">{{ __( 'Return' ) }}</a>
                        </div>
                    </div>
                    <div :class="form.main.disabled ? 'disabled' : ( form.main.errors.length > 0 ? 'error' : 'info' )" class="input-group flex border-2 rounded overflow-hidden">
                        <input v-model="form.main.value"
                            @blur="formValidation.checkField( form.main )"
                            @change="formValidation.checkField( form.main )"
                            :disabled="form.main.disabled"
                            type="text"
                            class="flex-auto text-primary outline-none h-10 px-2">
                        <button :disabled="form.main.disabled" @click="submit()" class="outline-none px-4 h-10"><slot name="save">{{ __( 'Save' ) }}</slot></button>
                    </div>
                    <p class="text-xs text-primary py-1" v-if="form.main.description && form.main.errors.length === 0">{{ form.main.description }}</p>
                    <p class="text-xs py-1 text-error-tertiary" v-bind:key="index" v-for="(error, index) of form.main.errors">
                        <span><slot name="error-required">{{ error.identifier }}</slot></span>
                    </p>
                </div>
                <div id="form-container" class="-mx-4 flex flex-wrap mt-4">
                    <div class="px-4 w-full md:w-1/2">
                        <div class="rounded ns-box shadow p-2" v-bind:key="index" v-for="( tab, index) of generalTab">
                            <ns-field v-bind:key="index" v-for="( field, index ) of tab.fields" :field="field"></ns-field>
                        </div>
                    </div>
                    <div class="px-4 w-full md:w-1/2">
                        <div id="tabbed-card">
                            <div id="card-header" class="flex ml-4 flex-wrap ns-tab">
                                <div @click="setTabActive( tab )" :class="tab.active ? 'active' : 'inactive'" v-for="( tab, index ) of validTabs" v-bind:key="index" class="tab cursor-pointer px-4 py-2 rounded-tl-lg rounded-tr-lg">
                                    {{ tab.label }}
                                </div>
                            </div>
                            <div class="ns-tab-item">
                                <div class="shadow p-2 rounded">
                                    <div class="flex flex-col" v-bind:key="index" v-for="( field, index ) of activeValidTab.fields">
                                        <ns-field :field="field"></ns-field>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
