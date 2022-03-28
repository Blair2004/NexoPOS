<script>
import FormValidation from '../../libraries/form-validation';
import { nsSnackBar, nsHttpClient } from '../../bootstrap';
import { __ } from '@/libraries/lang';
export default { 
    name: 'ns-create-coupons',
    mounted() {
        this.loadForm();
        // this.optionsSubject     =   new BehaviorSubject( this.options );
    },
    computed: {
        validTabs() {
            if ( this.form ) {
                const tabs  =   [];
                for( let tab in this.form.tabs ) {
                    if ([ 'selected_products', 'selected_categories' ].includes( tab ) ) {
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
            nsSnackBar,
            nsHttpClient,
            // optionsSubject: null,
            options: (new Array(40)).fill('').map( ( a, index ) =>  {
                return {
                    label: 'Foo' + index,
                    value: 'bar' + index
                }
            })
        }
    },
    props: [ 'submit-method', 'submit-url', 'return-url', 'src', 'rules' ],
    methods: {
        setTabActive( tab ) {
            this.validTabs.forEach( tab => tab.active = false );
            tab.active  =   true;
        },
        submit() {
            if ( this.formValidation.validateForm( this.form ).length > 0 ) {
                return nsSnackBar.error( this.$slots[ 'error-invalid-form' ] ? this.$slots[ 'error-invalid-form' ][0].text : 'No error message provided for having an invalid form.', this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : 'OK' )
                    .subscribe();
            }

            if ( this.submitUrl === undefined ) {
                return nsSnackBar.error( this.$slots[ 'error-no-submit-url' ] ? this.$slots[ 'error-no-submit-url' ][0].text : 'No error message provided for not having a valid submit url.', this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : 'OK' )
                    .subscribe();
            }

            this.formValidation.disableForm( this.form );

            const data  =   {
                ...this.formValidation.extractForm( this.form ),
            }

            nsHttpClient[ this.submitMethod ? this.submitMethod.toLowerCase() : 'post' ]( this.submitUrl, data )
                .subscribe( data => {
                    if ( data.status === 'success' ) {
                        return document.location   =   this.returnUrl;
                    }
                    this.formValidation.enableForm( this.form );
                }, ( error ) => {
                    nsSnackBar.error( error.message || __( 'An unexpected error occured.' ), undefined, {
                        duration: 5000
                    }).subscribe();
                    
                    if ( error.response ) {
                        this.formValidation.triggerError( this.form, error.response.data );
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
            <div class="flex flex-col">
                <div class="flex justify-between items-center">
                    <label for="title" class="font-bold my-2 text-primary"><slot name="title">No title Provided</slot></label>
                    <div for="title" class="text-sm my-2 text-primary">
                        <a v-if="returnUrl" :href="returnUrl" class="rounded-full border border-tertiary hover:bg-error-primary hover:border-transparent hover:text-white bg-input-background px-2 py-1">Return</a>
                    </div>
                </div>
                <div :class="form.main.disabled ? 'border-input-disabled' : form.main.errors.length > 0 ? 'border-error-primary' : 'border-input-button'" class="flex border-2 rounded overflow-hidden">
                    <input v-model="form.main.value" 
                        @blur="formValidation.checkField( form.main )" 
                        @change="formValidation.checkField( form.main )" 
                        :disabled="form.main.disabled"
                        type="text" 
                        :class="form.main.disabled ? 'bg-input-disabled' : 'bg-input-disabled'"
                        class="flex-auto text-primary outline-none h-10 px-2">
                    <button :disabled="form.main.disabled" :class="form.main.disabled ? 'bg-input-disabled border-input-disabled' : form.main.errors.length > 0 ? 'bg-error-primary border-error-secondary' : 'bg-input-edge border-input-edge'" @click="submit()" class="outline-none px-4 h-10 text-white border-l border-tertia"><slot name="save">Save</slot></button>
                </div>
                <p class="text-xs text-primary py-1" v-if="form.main.description && form.main.errors.length === 0">{{ form.main.description }}</p>
                <p class="text-xs py-1 text-error-primary" v-bind:key="index" v-for="(error, index) of form.main.errors">
                    <span><slot name="error-required">{{ error.identifier }}</slot></span>
                </p>
            </div>
            <div id="form-container" class="-mx-4 flex flex-wrap mt-4">
                <div class="px-4 w-full md:w-1/2">
                    <div class="rounded bg-input-background shadow p-2" v-bind:key="index" v-for="( tab, index) of generalTab">
                        <ns-field v-bind:key="index" v-for="( field, index ) of tab.fields" :field="field"></ns-field>
                    </div>
                </div>
                <div class="px-4 w-full md:w-1/2">
                    <div id="tabbed-card">
                        <div id="card-header" class="flex flex-wrap">
                            <div @click="setTabActive( tab )" :class="tab.active ? 'bg-tab-active' : 'bg-tab-inactive'" v-for="( tab, index ) of validTabs" v-bind:key="index" class="cursor-pointer px-4 py-2 rounded-tl-lg rounded-tr-lg text-white">
                                {{ tab.label }}
                            </div>
                        </div>
                        <div class="card-body bg-input-background rounded-br-lg rounded-bl-lg shadow p-2">
                            <div class="flex flex-col" v-bind:key="index" v-for="( field, index ) of activeValidTab.fields">
                                <ns-field :field="field"></ns-field>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>