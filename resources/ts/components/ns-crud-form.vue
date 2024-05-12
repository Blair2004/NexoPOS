<script lang="ts">
import { nsHooks, nsHttpClient, nsSnackBar } from '../bootstrap';
import FormValidation from '../libraries/form-validation';
import { __  } from '~/libraries/lang';
import popupResolver from '~/libraries/popup-resolver';
import popupCloser from '~/libraries/popup-closer';

export default {
    data: () => {
        return {
            form: {},
            globallyChecked: false,
            formValidation: new FormValidation,
            rows: []
        }
    }, 
    emits: [ 'updated', 'saved' ],
    mounted() {
        this.loadForm();
    },
    props: [ 'src', 'createUrl', 'fieldClass', 'returnUrl', 'submitUrl', 'submitMethod', 'disableTabs', 'queryParams', 'popup', 'optionAttributes' ],
    computed: {
        activeTabFields() {
            for( let identifier in this.form.tabs ) {
                if ( this.form.tabs[ identifier ].active ) {
                    return this.form.tabs[ identifier ].fields;
                }
            }
            return [];
        },
        activeTabIdentifier() {
            for( let identifier in this.form.tabs ) {
                if ( this.form.tabs[ identifier ].active ) {
                    return identifier;
                }
            }
            return {};
        }
    },
    methods: {
        __,
        popupResolver,
        toggle( identifier ) {
            for( let key in this.form.tabs ) {
                this.form.tabs[ key ].active    =   false;
            }
            this.form.tabs[ identifier ].active     =   true;
        },
        async handleSaved( event, activeTabIdentifier, field ) {
            this.form.tabs[ activeTabIdentifier ].fields.filter( __field => {
                if ( __field.name === field.name && event.data.entry ) {
                    __field.options.push({
                        label: event.data.entry[ this.optionAttributes.label ],
                        value: event.data.entry[ this.optionAttributes.value ]
                    });

                    __field.value = event.data.entry.id;
                }
            });
        },
        handleClose() {
            if ( this.popup ) {
                this.popupResolver( false );
            }
        },
        submit() {
            if ( this.formValidation.validateForm( this.form ).length > 0 ) {
                return nsSnackBar.error( __( 'Unable to proceed the form is not valid' ), __( 'Close' ) )
                    .subscribe();
            }

            this.formValidation.disableForm( this.form );

            if ( this.submitUrl === undefined ) {
                return nsSnackBar.error( __( 'No submit URL was provided' ), __( 'Okay' ) )
                    .subscribe();
            }

            nsHttpClient[ this.submitMethod ? this.submitMethod.toLowerCase() : 'post' ]( this.appendQueryParamas( this.submitUrl ), this.formValidation.extractForm( this.form ) )
                .subscribe( result => {
                    if ( result.status === 'success' ) {
                        /**
                         * This wil allow any external to hook into saving process
                         */
                        if ( this.popup ) {
                            this.popupResolver( result );
                        } else {
                            if ( this.submitMethod && this.submitMethod.toLowerCase() === 'post' && this.returnUrl !== false ) {
                                return document.location   =   result.data.editUrl || this.returnUrl;
                            } else {
                                nsSnackBar.info( result.message, __( 'Okay' ), { duration: 3000 }).subscribe();
                            }

                            this.$emit( 'saved', result );
                        }
                    }
                    this.formValidation.enableForm( this.form );
                }, ( error ) => {
                    nsSnackBar.error( error.message, undefined, {
                        duration: 5000
                    }).subscribe();
                    
                    if ( error.data !== undefined ) {
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
            return new Promise( ( resolve, reject ) => {
                const request   =   nsHttpClient.get( `${this.appendQueryParamas( this.src ) }` );
                    request.subscribe({
                        next: (f) => {
                            resolve( f );
                            this.form    =   this.parseForm( f.form );
                            nsHooks.doAction( 'ns-crud-form-loaded', this );
                            this.$emit( 'updated', this.form );
                        },
                        error: ( error ) => {
                            reject( error )
                            nsSnackBar.error( error.message, __( 'Okay' ), { duration: 0 }).subscribe();
                        }
                    });
            });
        },
        appendQueryParamas( url ) {
            if ( this.queryParams === undefined ) {
                return url;
            }

            const params    =   Object.keys(this.queryParams)
                .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(this.queryParams[key])}`)
                .join('&');

            if ( url.includes( '?' ) ) {
                return `${url}&${params}`;
            }

            return `${url}?${params}`;
        },
        parseForm( form ) {
            form.main.value     =   form.main.value === undefined ? '' : form.main.value;
            form.main           =   this.formValidation.createFields([ form.main ])[0];
            let index           =   0;

            for( let key in form.tabs ) {
                if ( index === 0 ) {
                    form.tabs[ key ].active  =   true;
                }

                form.tabs[ key ].active     =   form.tabs[ key ].active === undefined ? false : form.tabs[ key ].active;
                form.tabs[ key ].fields     =   this.formValidation.createFields( form.tabs[ key ].fields );

                index++;
            }

            return form;
        }
    },
}
</script>
<template>
    <div v-if="Object.values( form ).length === 0" class="flex items-center justify-center h-full">
        <ns-spinner />
    </div>
    <div class="form flex-auto" v-if="Object.values( form ).length > 0" :class="popup ? 'bg-box-background w-95vw md:w-2/3-screen' : ''" id="crud-form" >
        <div class="box-header border-b border-box-edge box-border p-2 flex justify-between items-center" v-if="popup">
            <h2 class="text-primary font-bold text-lg">{{ popup.params.title }}</h2>
            <div>
                <ns-close-button @click="handleClose()"></ns-close-button>
            </div>
        </div>
        <div v-if="Object.values( form ).length > 0" :class="popup ? 'p-2' : ''">
            <div class="flex flex-col">
                <div class="flex justify-between items-center" v-if="form.main">
                    <label for="title" class="font-bold my-2 text-primary">
                        <span v-if="form.main.name">{{ form.main.label }}</span>
                    </label>
                    <div for="title" class="text-sm my-2">
                        <a v-if="returnUrl && ! popup" :href="returnUrl" class="rounded-full border px-2 py-1 ns-inset-button error">{{ __( 'Go Back' ) }}</a>
                    </div>
                </div>
                <template v-if="form.main.name">
                    <div :class="form.main.disabled ? 'disabled' : form.main.errors.length > 0 ? 'error' : 'info'" class="input-group flex border-2 rounded overflow-hidden">
                        <input v-model="form.main.value" 
                            @keydown.enter="submit()"
                            @keypress="formValidation.checkField( form.main )"
                            @blur="formValidation.checkField( form.main )" 
                            @change="formValidation.checkField( form.main )" 
                            :disabled="form.main.disabled"
                            type="text" 
                            class="flex-auto outline-none h-10 px-2">
                        <button :disabled="form.main.disabled" :class="form.main.disabled ? 'disabled' : form.main.errors.length > 0 ? 'error' : ''" @click="submit()" class="outline-none px-4 h-10 text-white">{{ __( 'Save' ) }}</button>
                    </div>
                    <p class="text-xs text-primary py-1" v-if="form.main.description && form.main.errors.length === 0">{{ form.main.description }}</p>
                    <p :key="index" class="text-xs py-1 text-error-tertiary" v-for="(error,index) of form.main.errors">
                        <span v-if="error.identifier=== 'required'"><slot name="error-required">{{ error.identifier }}</slot></span>
                        <span v-if="error.identifier=== 'invalid'"><slot name="error-invalid">{{ error.message }}</slot></span>
                    </p>
                </template>
            </div>
            <div id="tabs-container" :class="popup ? 'mt-5' : 'my-5'" class="ns-tab" v-if="disableTabs !== 'true'">
                <div class="header flex ml-4" style="margin-bottom: -1px;">
                    <div :key="identifier" v-for="( tab , identifier ) of form.tabs" 
                        @click="toggle( identifier )" 
                        :class="tab.active ? 'active border border-b-transparent' : 'inactive border'" 
                        class="tab rounded-tl rounded-tr border px-3 py-2 cursor-pointer" style="margin-right: -1px">{{ tab.label }}</div>
                </div>
                <div class="ns-tab-item">
                    <div class="border p-4 rounded">
                        <div class="-mx-4 flex flex-wrap">
                            <div :key="`${activeTabIdentifier}-${key}`" :class="fieldClass || 'px-4 w-full md:w-1/2 lg:w-1/3'" v-for="(field,key) of activeTabFields">
                                <ns-field @saved="handleSaved( $event, activeTabIdentifier, field )" @blur="formValidation.checkField( field )" @change="formValidation.checkField( field )" :field="field"/>
                            </div>
                        </div>
                        <div class="flex justify-end" v-if="! form.main.name">
                            <div class="ns-button" :class="form.main.disabled ? 'default' : ( form.main.errors.length > 0 ? 'error' : 'info' )">
                                <button :disabled="form.main.disabled" @click="submit()" class="outline-none px-4 h-10 border-l">{{ __( 'Save' ) }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>