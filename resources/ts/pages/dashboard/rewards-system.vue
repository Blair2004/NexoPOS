<script>
import FormValidation from '~/libraries/form-validation';
import { nsSnackBar, nsHttpClient } from '~/bootstrap';
import { __ } from '~/libraries/lang';
export default {
    name: 'ns-rewards-system',
    mounted() {
        this.loadForm();
    },
    data: () => {
        return {
            formValidation: new FormValidation,
            form: {},
            nsSnackBar,
            nsHttpClient,
        }
    },
    props: [ 'submitMethod', 'submitUrl', 'returnUrl', 'src', 'rules' ],
    methods: {
        __,
        submit() {
            if ( this.form.rules.length === 0 ) {
                return nsSnackBar.error( __( 'No rules has been provided.' ) )
                    .subscribe();
            }

            if ( this.form.rules.filter( rule => {
                return rule.filter( field => ! ( field.value >= 0 ) && field.type !== 'hidden' ).length > 0;
            }).length > 0 ) {
                return nsSnackBar.error( __( 'No valid run were provided.' ) )
                    .subscribe();
            }

            if ( this.formValidation.validateForm( this.form ).length > 0 ) {
                return nsSnackBar.error( __( 'Unable to proceed, the form is invalid.' ), __( 'OK' ) )
                    .subscribe();
            }

            this.formValidation.disableForm( this.form );

            if ( this.submitUrl === undefined ) {
                return nsSnackBar.error( __( 'Unable to proceed, no valid submit URL is defined.' ), __( 'OK' ) )
                    .subscribe();
            }

            const data  =   {
                ...this.formValidation.extractForm( this.form ),
                rules: this.form.rules.map( rule => {
                    const fieldSet    =   {};

                    rule.forEach( f => {
                        fieldSet[ f.name ]  =   f.value;
                    });

                    return fieldSet;
                })
            }

            nsHttpClient[ this.submitMethod ? this.submitMethod.toLowerCase() : 'post' ]( this.submitUrl, data )
                .subscribe( data => {
                    if ( data.status === 'success' ) {
                        return document.location   =   this.returnUrl;
                    }
                    this.formValidation.enableForm( this.form );
                }, ( error ) => {
                    this.formValidation.triggerError( this.form, error.response.data );
                    this.formValidation.enableForm( this.form );
                    nsSnackBar.error( error.data.message || __( 'An unexpected error has occurred' ), undefined, {
                        duration: 5000
                    }).subscribe();
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
                if ( index === 0 ) {
                    form.tabs[ key ].active  =   true;
                }

                form.tabs[ key ].active     =   form.tabs[ key ].active === undefined ? false : form.tabs[ key ].active;
                form.tabs[ key ].fields     =   this.formValidation.createFields( form.tabs[ key ].fields );

                index++;
            }

            return form;
        },
        handleSaveEvent( serverResponse, field )  {
            try {
                field.options.push({
                    label: serverResponse.data.entry[ field.props.optionAttributes.label ],
                    value: serverResponse.data.entry[ field.props.optionAttributes.value ]
                });
                field.value     =   serverResponse.data.entry[ field.props.optionAttributes.value ];
            } catch ( exception ) {
                // something went wrong
            }
        },
        getRuleForm() {
            return JSON.parse( JSON.stringify( this.form.ruleForm ) );
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
                    <label for="title" class="font-bold my-2 text-primary"><slot name="title">{{ __( 'No title Provided' ) }}</slot></label>
                    <div for="title" class="text-sm my-2 text-primary">
                        <a v-if="returnUrl" :href="returnUrl" class="rounded-full border ns-inset-button error px-2 py-1">Return</a>
                    </div>
                </div>
                <div :class="form.main.disabled ? 'disabled' : form.main.errors.length > 0 ? 'error' : 'info'" class="input-group flex border-2 rounded overflow-hidden">
                    <input v-model="form.main.value"
                        @blur="formValidation.checkField( form.main )"
                        @change="formValidation.checkField( form.main )"
                        :disabled="form.main.disabled"
                        type="text"
                        class="flex-auto text-primary outline-none h-10 px-2">
                    <button :disabled="form.main.disabled" @click="submit()" class="outline-none px-4 h-10 border-l border-tertiary"><slot name="save">{{ __( 'Save' ) }}</slot></button>
                </div>
                <p class="text-xs text-primary py-1" v-if="form.main.description && form.main.errors.length === 0">{{ form.main.description }}</p>
                <p class="text-xs py-1 text-error-tertiary" v-bind:key="index" v-for="(error, index) of form.main.errors">
                    <span><slot name="error-required">{{ error.identifier }}</slot></span>
                </p>
            </div>
            <div id="points-wrapper" class="flex -mx-4 mt-4">
                <div class="w-full md:w-1/3 lg:1/4 px-4">
                    <div class="ns-box rounded shadow">
                        <div class="header ns-box-header border-b p-2">{{ __( 'General' ) }}</div>
                        <div class="body p-2">
                            <ns-field class="mb-2" @saved="handleSaveEvent( $event, field )" v-bind:key="index" :field="field" v-for="(field,index) of form.tabs.general.fields"></ns-field>
                        </div>
                    </div>
                    <div class="ns-box rounded">
                        <div class="ns-body p-2 flex justify-between items-center my-3">
                            <slot name="add"><span class="text-primary">{{ __( 'Add Rule' ) }}</span></slot>
                            <div class="ns-button info">
                                <button @click="addRule()" class="rounded font-semibold flex items-center justify-center h-10 w-10">
                                    <i class="las la-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-full md:w-2/3 lg:3/4 px-4 -m-3 flex flex-wrap items-start justify-start">
                    <div class="w-full md:w-1/2 p-3" v-bind:key="index" v-for="(rule,index) of form.rules">
                        <div class="rounded shadow ns-box flex-auto">
                            <div class="body p-2">
                                <ns-field class="mb-2" :field="field" v-bind:key="fieldIndex" v-for="(field,fieldIndex) of rule"></ns-field>
                            </div>
                            <div class="header border-t ns-box-footer p-2 flex justify-end">
                                <ns-button @click="removeRule(index)" type="error">
                                    <i class="las la-times"></i>
                                </ns-button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
