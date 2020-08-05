<script>
import FormValidation from '../../libraries/form-validation';
import { nsSnackBar, nsHttpClient } from '../../bootstrap';
export default {
    name: 'ns-create-coupons',
    mounted() {
        this.loadForm();
        console.log( this.options );
    },
    data() {
        return {
            formValidation: new FormValidation,
            form: {},
            nsSnackBar,
            nsHttpClient,
            options: (new Array(40)).fill('').map( a =>  {
                return {
                    label: 'Foo',
                    value: 'bar'
                }
            })
        }
    },
    props: [ 'submit-method', 'submit-url', 'return-link', 'src', 'rules' ],
    methods: {
        submit() {
            if ( this.form.rules.length === 0 ) {
                return nsSnackBar.error( this.$slots[ 'error-no-rules' ] ? this.$slots[ 'error-no-rules' ] : 'No error message is defined when no rules is provided' )
                    .subscribe();
            }

            if ( this.form.rules.filter( rule => {
                return rule.filter( field => ! field.value && field.type !== 'hidden' ).length > 0;
            }).length > 0 ) {
                return nsSnackBar.error( this.$slots[ 'error-no-valid-rules' ] ? this.$slots[ 'error-no-valid-rules' ] : 'No error message is defined when no valid rules is provided' )
                    .subscribe();
            }

            if ( ! this.formValidation.validateForm( this.form ) ) {
                return nsSnackBar.error( this.$slots[ 'error-invalid-form' ] ? this.$slots[ 'error-invalid-form' ][0].text : 'No error message provided for having an invalid form.', this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : 'OK' )
                    .subscribe();
            }

            this.formValidation.disableForm( this.form );

            if ( this.submitUrl === undefined ) {
                return nsSnackBar.error( this.$slots[ 'error-no-submit-url' ] ? this.$slots[ 'error-no-submit-url' ][0].text : 'No error message provided for not having a valid submit url.', this.$slots[ 'okay' ] ? this.$slots[ 'okay' ][0].text : 'OK' )
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
                .subscribe( result => {
                    if ( result.data.status === 'success' ) {
                        return document.location   =   this.returnLink;
                    }
                    this.formValidation.enableForm( this.form );
                }, ( error ) => {
                    nsSnackBar.error( error.response.data.message, undefined, {
                        duration: 5000
                    }).subscribe();
                    this.formValidation.triggerError( this.form, error.response.data );
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
                this.form    =   this.parseForm( f.data.form );
            });
        },
        parseForm( form ) {
            form.main.value     =   form.main.value === undefined ? '' : form.main.value;
            form.main           =   this.formValidation.createForm([ form.main ])[0];
            let index           =   0;

            console.log( form );

            for( let key in form.tabs ) {
                if ( index === 0 ) {
                    form.tabs[ key ].active  =   true;
                }

                form.tabs[ key ].active     =   form.tabs[ key ].active === undefined ? false : form.tabs[ key ].active;
                form.tabs[ key ].fields     =   this.formValidation.createForm( form.tabs[ key ].fields );

                index++;
            }

            return form;
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
                    <div class="rounded bg-white shadow p-2" v-bind:key="index" v-for="( tab, index) of form.tabs">
                        <ns-field v-bind:key="index" v-for="( field, index ) of tab.fields" :field="field"></ns-field>
                    </div>
                </div>
                <div class="px-4 w-full md:w-1/2">
                    <div id="tabbed-card">
                        <div id="card-header" class="flex flex-wrap">
                            <div class="bg-white cursor-pointer px-4 py-2 rounded-tl-lg rounded-tr-lg">
                                Products
                            </div>
                            <div class="bg-gray-100 cursor-pointer px-4 py-2 rounded-tl-lg rounded-tr-lg">
                                Categories
                            </div>
                        </div>
                        <div class="card-body bg-white rounded-br-lg rounded-bl-lg shadow p-2">
                            <div class="flex flex-col">
                                <label for="" class="font-medium text-gray-700">Something</label>
                                <ns-multiselect v-bind:options="options"></ns-multiselect>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>