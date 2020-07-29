const { Vue, EventEmitter, nsHttpClient, nsSnackBar }   =   require( '../bootstrap' );
const FormValidation                        =   require( "./../libraries/form-validation" ).default;

const nsCrud    =   Vue.component( 'ns-crud-form', {
    data: () => {
        return {
            form: {},
            globallyChecked: false,
            formValidation: new FormValidation,
            rows: []
        }
    }, 
    mounted() {
        console.log( this );
        this.loadForm();
    },
    props: [ 'src', 'create-link', 'field-class', 'return-link', 'submit-url', 'submit-method' ],
    computed: {
        activeTabFields() {
            for( identifier in this.form.tabs ) {
                if ( this.form.tabs[ identifier ].active ) {
                    return this.form.tabs[ identifier ].fields;
                }
            }
            return [];
        }
    },
    methods: {
        toggle( identifier ) {
            for( key in this.form.tabs ) {
                this.form.tabs[ key ].active    =   false;
            }
            this.form.tabs[ identifier ].active     =   true;
        },
        handleShowOptions( e ) {
            this.rows.forEach( row => {
                if ( row.$id !== e.$id ) {
                    row.$toggled    =   false;
                }
            });
        },
        submit() {
            if ( ! this.formValidation.validateForm( this.form ) ) {
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
                    // document.location   =   this.returnLink;
                }, ( error ) => {
                    // console.log( error.response )
                    nsSnackBar.error( error.response.data.message, undefined, {
                        duration: 5000
                    }).subscribe();
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
                console.log( this.form );
            });
        },
        parseForm( form ) {
            form.main.value     =   form.main.value === undefined ? '' : form.main.value;
            form.main           =   this.formValidation.createForm([ form.main ])[0];
            let index           =   0;

            for( key in form.tabs ) {
                if ( index === 0 ) {
                    form.tabs[ key ].active  =   true;
                }

                form.tabs[ key ].active     =   form.tabs[ key ].active === undefined ? false : form.tabs[ key ].active;
                form.tabs[ key ].fields     =   this.formValidation.createForm( form.tabs[ key ].fields );

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
                <p class="text-xs py-1 text-red-500" v-for="error of form.main.errors">
                    <span><slot name="error-required">{{ error.identifier }}</slot></span>
                </p>
            </div>
            <div id="tabs-container" class="my-5">
                <div class="header flex" style="margin-bottom: -1px;">
                    <div v-for="( tab , identifier ) of form.tabs" @click="toggle( identifier )" :class="tab.active ? 'border-b-0 bg-white' : 'border bg-gray-200'" class="tab rounded-tl rounded-tr border border-gray-400  px-3 py-2 text-gray-700 cursor-pointer" style="margin-right: -1px">{{ tab.label }}</div>
                </div>
                <div v-for="tab of form.tabs" class="border border-gray-400 p-4 bg-white">
                    <div class="-mx-4 flex flex-wrap">
                        <div :class="fieldClass || 'px-4 w-full md:w-1/2 lg:w-1/3'" v-for="field of activeTabFields">
                            <ns-field @blur="formValidation.checkField( field )" @change="formValidation.checkField( field )" :field="field"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `,
});

module.exports   =   nsCrud;