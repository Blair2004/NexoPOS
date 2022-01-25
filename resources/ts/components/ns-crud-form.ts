import { nsHooks, nsHttpClient, nsSnackBar } from '../bootstrap';
import Vue from 'vue';
import FormValidation from '../libraries/form-validation';
import { __ } from '@/libraries/lang';
const nsCrudForm    =   Vue.component( 'ns-crud-form', {
    data: () => {
        return {
            form: {},
            globallyChecked: false,
            formValidation: new FormValidation,
            rows: []
        }
    }, 
    mounted() {
        this.loadForm();
    },
    props: [ 'src', 'create-url', 'field-class', 'return-url', 'submit-url', 'submit-method', 'disable-tabs' ],
    computed: {
        activeTabFields() {
            for( let identifier in this.form.tabs ) {
                if ( this.form.tabs[ identifier ].active ) {
                    return this.form.tabs[ identifier ].fields;
                }
            }
            return [];
        }
    },
    methods: {
        __,
        toggle( identifier ) {
            for( let key in this.form.tabs ) {
                this.form.tabs[ key ].active    =   false;
            }
            this.form.tabs[ identifier ].active     =   true;
        },
        /**
         * Dpeca
         * @param {Object} e Something
         * @deprecated
         */
        handleShowOptions( e ) {
            this.rows.forEach( row => {
                if ( row.$id !== e.$id ) {
                    row.$toggled    =   false;
                }
            });
        },
        submit() {
            if ( this.formValidation.validateForm( this.form ).length > 0 ) {
                return nsSnackBar.error( this.$slots[ 'error-invalid-form' ] ? this.$slots[ 'error-invalid-form' ][0].text : 'No error message provided for having an invalid form.', this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : 'OK' )
                    .subscribe();
            }

            this.formValidation.disableForm( this.form );

            if ( this.submitUrl === undefined ) {
                return nsSnackBar.error( this.$slots[ 'error-no-submit-url' ] ? this.$slots[ 'error-no-submit-url' ][0].text : 'No error message provided for not having a valid submit url.', this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : 'OK' )
                    .subscribe();
            }

            nsHttpClient[ this.submitMethod ? this.submitMethod.toLowerCase() : 'post' ]( this.submitUrl, this.formValidation.extractForm( this.form ) )
                .subscribe( result => {
                    if ( result.status === 'success' ) {
                        if ( this.returnUrl && this.returnUrl.length > 0 ) {
                            return document.location   =   this.returnUrl;
                        }
                        this.$emit( 'save', result );
                    }
                    this.formValidation.enableForm( this.form );
                }, ( error ) => {
                    nsSnackBar.error( error.message, undefined, {
                        duration: 5000
                    }).subscribe();
                    
                    this.formValidation.triggerError( this.form, error );
                    this.formValidation.enableForm( this.form );
                })
        },
        handleGlobalChange( event ) {
            this.globallyChecked    =   event;
            this.rows.forEach( r => r.$checked = event );
        },
        loadForm() {
            const request   =   nsHttpClient.get( `${this.src}` );
            request.subscribe({
                next: (f:any) => {
                    this.form    =   this.parseForm( f.form );
                    nsHooks.doAction( 'ns-crud-form-loaded', this );
                    this.$emit( 'updated', this.form );
                },
                error: ( error ) => {
                    nsSnackBar.error( error.message, 'OKAY', { duration: 0 }).subscribe();
                }
            });
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
    template: `
    <div class="form flex-auto" id="crud-form">
        <div v-if="Object.values( form ).length === 0" class="flex items-center justify-center h-full">
            <ns-spinner />
        </div>
        <div v-if="Object.values( form ).length > 0">
            <div class="flex flex-col">
                <div class="flex justify-between items-center" v-if="form.main">
                    <label for="title" class="font-bold my-2 text-gray-700 dark:text-slate-300">
                        <span v-if="form.main.name">{{ form.main.label }}</span>
                    </label>
                    <div for="title" class="text-sm my-2 text-gray-700 dark:text-slate-300">
                        <a v-if="returnUrl" :href="returnUrl" class="rounded-full
                        border
                        px-2
                        py-1

                        border-gray-400
                        dark:text-slate-300
                        bg-white
                        
                        hover:border-red-600
                        hover:bg-red-600
                        hover:text-white

                        dark:bg-transparent
                        dark:border-slate-600
                        dark:hover:bg-slate-600
                        ">{{ __( 'Go Back' ) }}</a>
                    </div>
                </div>
                <template v-if="form.main.name">
                    <div :class="form.main.disabled ? 'border-gray-500 dark:bg-slate-600' : form.main.errors.length > 0 ? 'border-red-600' : 'border-blue-500 dark:border-slate-600'" class="flex border-2 rounded overflow-hidden">
                        <input v-model="form.main.value" 
                            @blur="formValidation.checkField( form.main )" 
                            @change="formValidation.checkField( form.main )" 
                            :disabled="form.main.disabled"
                            type="text" 
                            :class="form.main.disabled ? 'bg-gray-400' : ''"
                            class="flex-auto text-gray-700 outline-none h-10 px-2 dark:bg-slate-800 dark:text-slate-300">
                        <button :disabled="form.main.disabled" :class="form.main.disabled ? 'bg-gray-500' : form.main.errors.length > 0 ? 'bg-red-500' : 'bg-blue-500 dark:bg-slate-600'" @click="submit()" class="outline-none px-4 h-10 text-white border-l border-gray-400"><slot name="save">Save</slot></button>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-slate-300 py-1" v-if="form.main.description && form.main.errors.length === 0">{{ form.main.description }}</p>
                    <p class="text-xs py-1 text-red-500" v-for="error of form.main.errors">
                        <span v-if="error.identifier=== 'required'"><slot name="error-required">{{ error.identifier }}</slot></span>
                        <span v-if="error.identifier=== 'invalid'"><slot name="error-invalid">{{ error.message }}</slot></span>
                    </p>
                </template>
            </div>
            <div id="tabs-container" class="my-5" v-if="disableTabs !== 'true'">
                <div class="header flex" style="margin-bottom: -1px;">
                    <div v-for="( tab , identifier ) of form.tabs" 
                        @click="toggle( identifier )" 
                        :class="tab.active ? 'border-b-0 bg-white dark:bg-slate-700' : 'border bg-gray-200 dark:bg-slate-800'" 
                        class="tab rounded-tl rounded-tr border dark:border-slate-800 border-gray-400  px-3 py-2 text-gray-700 dark:text-slate-300 cursor-pointer" style="margin-right: -1px">{{ tab.label }}</div>
                </div>
                <div class="border border-gray-400 p-4 bg-white dark:bg-slate-700 dark:border-slate-800">
                    <div class="-mx-4 flex flex-wrap">
                        <div :class="fieldClass || 'px-4 w-full md:w-1/2 lg:w-1/3'" v-for="field of activeTabFields">
                            <ns-field @blur="formValidation.checkField( field )" @change="formValidation.checkField( field )" :field="field"/>
                        </div>
                    </div>
                    <div class="flex justify-end" v-if="! form.main.name">
                        <button :disabled="form.main.disabled" :class="form.main.disabled ? 'bg-gray-500' : form.main.errors.length > 0 ? 'bg-red-500' : 'bg-blue-500'" @click="submit()" class="outline-none px-4 h-10 text-white border-l border-gray-400"><slot name="save">Save</slot></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `,
});

export { nsCrudForm };