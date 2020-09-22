
<template>
    <div class="form flex-auto" id="crud-form">
        <div v-if="Object.values( form ).length === 0" class="flex items-center h-full justify-center flex-auto">
            <ns-spinner/>
        </div>
        <template v-if="Object.values( form ).length > 0">
            <div class="flex flex-col">
                <div class="flex justify-between items-center">
                    <label for="title" class="font-bold my-2 text-gray-700">{{ form.main.label }}</label>
                    <div for="title" class="text-sm my-2 text-gray-700">
                        <a v-if="returnUrl" :href="returnUrl" class="rounded-full border border-gray-400 hover:bg-red-600 hover:text-white bg-white px-2 py-1">Return</a>
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
                <div class="px-4 w-full">
                    <div id="tabbed-card" class="mb-8" :key="variation_index" v-for="(variation, variation_index) of form.variations">
                        <div id="card-header" class="flex flex-wrap justify-between">
                            <div class="flex flex-wrap">
                                <div @click="setTabActive( index, variation.tabs )" :class="tab.active ? 'bg-white' : 'bg-gray-100'" v-for="( tab, index ) in variation.tabs" v-bind:key="index" class="cursor-pointer text-gray-700 px-4 py-2 rounded-tl-lg rounded-tr-lg flex justify-between">
                                    <span class="block mr-2">{{ tab.label }}</span>
                                    <span v-if="tab.errors && tab.errors.length > 0" class="rounded-full bg-red-400 text-white h-6 w-6 flex font-semibold items-center justify-center">{{ tab.errors.length }}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-center -mx-1">
                                <!-- <div class="px-1" v-if="form.variations.length > 1 && variation_index > 0">
                                    <button @click="deleteVariation( variation_index )" class="rounded-full h-8 w-8 flex items-center justify-center bg-red-400 text-white">
                                        <i class="las la-times"></i>
                                    </button>
                                </div>
                                <div class="px-1">
                                    <button @click="newVariation()" class="rounded-full h-8 w-8 flex items-center justify-center bg-green-400 text-white">
                                        <i class="las la-plus"></i>
                                    </button>
                                </div>
                                <div class="px-1">
                                    <button @click="duplicate( variation )" class="rounded-full h-8 w-8 flex items-center justify-center bg-blue-400 text-white">
                                        <i class="las la-copy"></i>
                                    </button>
                                </div> -->
                            </div>
                        </div>
                        <div class="card-body bg-white rounded-br-lg rounded-bl-lg shadow p-2">
                            <div class="-mx-4 flex flex-wrap" v-if="getActiveTabKey( variation.tabs ) !== 'images'">
                                <template v-for="( field, index ) of getActiveTab( variation.tabs ).fields">
                                    <div :key="index" class="flex flex-col px-4 w-full md:w-1/2 lg:w-1/3">
                                        <ns-field @change="detectChange( variation_index, $event )" :field="field"></ns-field>
                                    </div>
                                </template>
                            </div>
                            <div class="-mx-4 flex flex-wrap" v-if="getActiveTabKey( variation.tabs ) === 'images'">
                                <div class="flex flex-col px-4 w-full md:w-1/2 lg:w-1/3">
                                    <div class="rounded border flex bg-white justify-between p-2 items-center">
                                        <span>Add Images</span>
                                        <button @click="addImage( variation )" class="rounded-full border flex items-center justify-center w-8 h-8 bg-white hover:bg-blue-400 hover:text-white">
                                            <i class="las la-plus-circle"></i>
                                        </button>
                                    </div>
                                </div>
                                <div
                                    :key="index" 
                                    v-for="( group, index ) of getActiveTab( variation.tabs ).groups" 
                                    class="flex flex-col px-4 w-full md:w-1/2 lg:w-1/3 mb-4">
                                    <div class="rounded border flex flex-col bg-white p-2">
                                        <ns-field :key="index" v-for="(field, index) of group" :field="field"></ns-field>
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
<script>
import FormValidation from '../../libraries/form-validation'
import { nsSnackBar, nsHttpClient } from '../../bootstrap';

export default {
    data: () => {
        return {
            formValidation: new FormValidation,
            nsSnackBar,
            nsHttpClient,
            _sampleVariation: null,
            form: '',
        }
    },
    computed: {
        defaultVariation() {
            const newVariation     =   new Object;

            for( let tabIndex in this._sampleVariation.tabs ) {
                newVariation[ tabIndex ]            =   new Object;
                newVariation[ tabIndex ].label      =   this._sampleVariation.tabs[ tabIndex ].label;
                newVariation[ tabIndex ].active     =   this._sampleVariation.tabs[ tabIndex ].active;
                newVariation[ tabIndex ].fields     =   this._sampleVariation.tabs[ tabIndex ].fields
                    .filter( field => {
                        console.log( field );
                        return ! [ 'category_id', 'product_type', 'stock_management', 'expires' ].includes( field.name );
                    })
                    .map( field => {
                    field.value     =   '';
                    return field;
                });
            }

            return {
                id: '',
                tabs: newVariation
            };
        }
    },  
    props: [ 'submit-method', 'submit-url', 'return-url', 'src', 'units-url' ],
    methods: {
        detectChange( variation_index, field ) {
            if ( [ 'unit_group' ].includes( field.name ) ) {
                switch( field.name ) {
                    case 'unit_group' :
                        this.loadOptionsFor( 'purchase_unit_ids', field.value, variation_index );
                        this.loadOptionsFor( 'transfer_unit_ids', field.value, variation_index );
                        this.loadOptionsFor( 'selling_unit_ids', field.value, variation_index );
                    break;
                }
            }
        },
        loadOptionsFor( fieldName, value, variation_index ) {
            nsHttpClient.get( this.unitsUrl.replace( '{id}', value ) )
                .subscribe( result => {
                    this.form.variations[ variation_index ].tabs.units.fields.forEach( _field => {
                        if ( _field.name === fieldName ) {
                            _field.options    =   result.map( option => {
                                return {
                                    label: option.name,
                                    value: option.id,
                                    selected: false,
                                };
                            })
                        }
                    });
                    this.$forceUpdate();
                })
        },
        submit() {
            let formValidGlobally   =   true;
            this.formValidation.validateFields([ this.form.main ]); 

            const validity  =   this.form.variations.map( variation => {
                return this.formValidation.validateForm( variation );
            }).filter( v => v.length > 0 );

            if ( validity.length > 0 || Object.values( this.form.main.errors ).length > 0 ) {
                return nsSnackBar.error( this.$slots[ 'error-form-invalid' ] ? this.$slots[ 'error-form-invalid' ][0].text : 'No error has been provided for the slot "error-form-invalid"' ).subscribe();
            }

            /**
             * If there are more than one
             * primary image, we'll block the process
             */
            const images    =   this.form.variations.map( (v,i) => {
                return v.tabs.images.groups.filter( fields => {
                    return fields.filter( f => f.name === 'primary' && f.value === 1 ).length > 0;
                });
            })

            if ( images[0] && images[0].length > 1 ) {
                return nsSnackBar.error( this.$slots[ 'error-multiple-primary' ] ? this.$slots[ 'error-multiple-primary' ][0].text : 'No error has been provided for the slot "error-multiple-primary"' ).subscribe();
            }

            /**
             * let's correctly extract 
             * the form before submitting that
             */
            const data  =   {
                ...this.formValidation.extractForm( this.form ),
                variations: this.form.variations.map( (v,i) => {
                    const data  =   this.formValidation.extractForm( v );
                    if ( i === 0 ) {
                        data[ '$primary' ]  =   true;
                    }

                    data[ 'images' ]    =   v.tabs.images.groups.map( fields => {
                        return this.formValidation.extractFields( fields );
                    });

                    return data;
                })
            }

            nsHttpClient[ this.submitMethod ? this.submitMethod.toLowerCase() : 'post' ]( this.submitUrl, data )
                .subscribe( data => {
                    if ( data.status === 'success' ) {
                        if ( this.returnUrl !== false ) {
                            return document.location   =   this.returnUrl;
                        }
                        this.$emit( 'save' );
                    }
                    this.formValidation.enableForm( this.form );
                }, ( error ) => {
                    nsSnackBar.error( error.message, undefined, {
                        duration: 5000
                    }).subscribe();
                    this.formValidation.triggerError( this.form, error.response.data );
                    this.formValidation.enableForm( this.form );
                })
        },
        deleteVariation( index ) {
            if ( confirm( this.$slots[ 'delete-variation' ] ? this.$slots[ 'delete-variation' ][0].text : 'No error message provided with code "delete-variation"' ) ) {
                this.form.variations.splice( index, 1 );
            }
        },
        setTabActive( activeIndex, tabs ) {
            for( let _index in tabs ) {
                if ( _index !== activeIndex ) {
                    tabs[ _index ].active    =   false;
                }
            }

            tabs[ activeIndex ].active  =   true;
        },  
        duplicate( variation ) {
            this.form.variations.push( Object.assign({}, variation ));
        },
        newVariation() {
            this.form.variations.push( this.defaultVariation );
        },
        getActiveTab( tabs ) {
            for( let key in tabs ) {
                if ( tabs[ key ].active ) {
                    return tabs[ key ];
                }
            }

            return false;
        }, 
        getActiveTabKey( tabs ) {
            for( let key in tabs ) {
                if ( tabs[ key ].active ) {
                    return key;
                }
            }

            return false;
        },
        parseForm( form ) {
            form.main.value     =   form.main.value === undefined ? '' : form.main.value;
            form.main           =   this.formValidation.createFields([ form.main ])[0];

            form.variations.forEach( ( variation, _index ) => {
                let index           =   0;
                for( let key in variation.tabs ) {

                    /**
                     * here we need to explicitely remove the
                     * name field as this is replaced by the top field.
                     * We also save the default variation as that's used for variations
                     */
                    if ( index === 0 && variation.tabs[ key ].active === undefined ) {
                        variation.tabs[ key ].active    =   true;
                        this._sampleVariation           =   Object.assign({}, variation );
                        if ( variation.tabs[ key ].fields ) {
                            variation.tabs[ key ].fields    =   this.formValidation.createFields( variation.tabs[ key ].fields.filter( f => f.name !== 'name' ) );
                        }
                    } else {
                        if ( variation.tabs[ key ].fields ) {
                            variation.tabs[ key ].fields    =   this.formValidation.createFields( variation.tabs[ key ].fields );
                        }
                    }

                    variation.tabs[ key ].active    =   variation.tabs[ key ].active === undefined ? false : variation.tabs[ key ].active;

                    index++;
                }
            });

            return form;
        },
        loadForm() {
            const request   =   nsHttpClient.get( `${this.src}` );
            request.subscribe( f => {
                this.form    =   this.parseForm( f.form );
            });
        },

        addImage( variation ) {
            variation.tabs.images.groups.push(
                this.formValidation.createFields( JSON.parse( JSON.stringify( variation.tabs.images.fields ) ) )
            );
        }
    },
    mounted() {
        this.loadForm();
    },
    name: 'ns-manage-products',
}
</script>