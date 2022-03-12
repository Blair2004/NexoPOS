
<template>
    <div class="form flex-auto" id="crud-form">
        <div v-if="Object.values( form ).length === 0" class="flex items-center h-full justify-center flex-auto">
            <ns-spinner/>
        </div>
        <template v-if="Object.values( form ).length > 0">
            <div class="flex flex-col">
                <div class="flex justify-between items-center">
                    <label for="title" class="font-bold my-2 text-primary">{{ form.main.label }}</label>
                    <div for="title" class="text-sm my-2 text-primary">
                        <a v-if="returnUrl" :href="returnUrl" class="rounded-full border ns-inset-button error hover:bg-error-primary  px-2 py-1">{{ __( 'Return' ) }}</a>
                    </div>
                </div>
                <div :class="form.main.disabled ? '' : form.main.errors.length > 0 ? 'border-error-primary' : ''" class="input-group info flex border-2 rounded overflow-hidden">
                    <input v-model="form.main.value" 
                        @blur="formValidation.checkField( form.main )" 
                        @change="formValidation.checkField( form.main )" 
                        :disabled="form.main.disabled"
                        type="text" 
                        :class="form.main.disabled ? '' : ''"
                        class="flex-auto text-primary outline-none h-10 px-2">
                    <button :disabled="form.main.disabled" :class="form.main.disabled ? '' : form.main.errors.length > 0 ? 'bg-error-primary' : ''" @click="submit()" class="outline-none px-4 h-10 rounded-none"><slot name="save">{{ __( 'Save' ) }}</slot></button>
                </div>
                <p class="text-xs text-primary py-1" v-if="form.main.description && form.main.errors.length === 0">{{ form.main.description }}</p>
                <p class="text-xs py-1 text-error-primary" v-bind:key="index" v-for="(error, index) of form.main.errors">
                    <span><slot name="error-required">{{ error.identifier }}</slot></span>
                </p>
            </div>
            <div id="form-container" class="-mx-4 flex flex-wrap mt-4">
                <div class="px-4 w-full">
                    <div id="tabbed-card" class="mb-8" :key="variation_index" v-for="(variation, variation_index) of form.variations">
                        <div id="card-header" class="flex flex-wrap justify-between ns-tab">
                            <div class="flex flex-wrap">
                                <div @click="setTabActive( index, variation.tabs )" :class="tab.active ? 'active' : 'inactive'" v-for="( tab, index ) in variation.tabs" v-bind:key="index" class="tab cursor-pointer text-primary px-4 py-2 rounded-tl-lg rounded-tr-lg flex justify-between">
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
                        <div class="card-body ns-tab-item rounded-br-lg rounded-bl-lg shadow p-2">
                            <div class="-mx-4 flex flex-wrap" v-if="! [ 'images', 'units' ].includes( getActiveTabKey( variation.tabs ) )">
                                <template v-for="( field, index ) of getActiveTab( variation.tabs ).fields">
                                    <div :key="index" class="flex flex-col px-4 w-full md:w-1/2 lg:w-1/3">
                                        <ns-field :field="field"></ns-field>
                                    </div>
                                </template>
                            </div>
                            <div class="-mx-4 flex flex-wrap text-primary" v-if="getActiveTabKey( variation.tabs ) === 'images'">
                                <div class="flex flex-col px-4 w-full md:w-1/2 lg:w-1/3">
                                    <div class="rounded border border-box-elevation-edge bg-box-elevation-background flex justify-between p-2 items-center">
                                        <span>{{ __( 'Add Images' ) }}</span>
                                        <button @click="addImage( variation )" class="outline-none rounded-full border flex items-center justify-center w-8 h-8 ns-inset-button info">
                                            <i class="las la-plus-circle"></i>
                                        </button>
                                    </div>
                                </div>
                                <div
                                    :key="index" 
                                    v-for="( group, index ) of getActiveTab( variation.tabs ).groups" 
                                    class="flex flex-col px-4 w-full md:w-1/2 lg:w-1/3 mb-4">
                                    <div class="rounded border border-box-elevation-edge flex flex-col overflow-hidden">
                                        <div class="p-2">
                                            <ns-field :key="index" v-for="(field, index) of group" :field="field"></ns-field>
                                        </div>
                                        <div @click="removeImage( variation, group )" class="text-center py-2 border-t border-box-elevation-edge text-sm cursor-pointer">
                                            {{ __( 'Remove Image' ) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="-mx-4 flex flex-wrap" v-if="getActiveTabKey( variation.tabs ) === 'units'">
                                <div class="px-4 w-full md:w-1/2 lg:w-1/3">
                                    <ns-field @change="loadAvailableUnits( getActiveTab( variation.tabs ) )" :field="getActiveTab( variation.tabs ).fields[0]"></ns-field>
                                    <ns-field @change="loadAvailableUnits( getActiveTab( variation.tabs ) )" :field="getActiveTab( variation.tabs ).fields[1]"></ns-field>
                                </div>
                                <template v-for="(field,index) of getActiveTab( variation.tabs ).fields">
                                    <div v-if="field.type === 'group'" class="px-4 w-full lg:w-2/3" :key="index">
                                        <div class="mb-2">
                                            <label class="font-medium text-primary">{{ field.label }}</label>
                                            <p class="py-1 text-sm text-primary">{{ field.description }}</p>
                                        </div>
                                        <div class="mb-2">
                                            <div @click="addUnitGroup( field )" class="border-dashed border-2 p-1 bg-box-elevation-background border-box-elevation-edge flex justify-between items-center text-primary cursor-pointer rounded-lg">
                                                <span class="rounded-full border-2 ns-inset-button info h-8 w-8 flex items-center justify-center">
                                                    <i class="las la-plus-circle"></i>
                                                </span>
                                                <span>{{ __( 'New Group' ) }}</span>
                                            </div>
                                        </div>
                                        <div class="-mx-4 flex flex-wrap">
                                            <div class="px-4 w-full md:w-1/2 mb-4" :key="index" v-for="(group_fields,index) of field.groups">
                                                <div class="shadow rounded overflow-hidden bg-box-elevation-background text-primary">
                                                    <div class="border-b text-sm p-2 flex justify-between text-primary border-box-elevation-edge">
                                                        <span>{{ __( 'Available Quantity' ) }}</span>
                                                        <span>{{ getUnitQuantity( group_fields ) }}</span>
                                                    </div>
                                                    <div class="p-2 mb-2">
                                                        <ns-field :field="field" v-for="(field,index) of group_fields" :key="index"></ns-field>
                                                    </div>
                                                    <div @click="removeUnitPriceGroup( group_fields, field.groups )" class="p-1 hover:bg-error-primary border-t border-box-elevation-edge flex items-center justify-center cursor-pointer font-medium">
                                                        {{ __( 'Delete' ) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
<script>
import FormValidation from '@/libraries/form-validation'
import { nsSnackBar, nsHttpClient } from '@/bootstrap';
import nsPosConfirmPopupVue from '@/popups/ns-pos-confirm-popup.vue';
import { __ } from '@/libraries/lang';
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
        __,
        
        getUnitQuantity( fields ) {
            const quantity  =   fields.filter( f => f.name === 'quantity' ).map( f => f.value );
            return quantity.length > 0 ? quantity[0] : 0;
        },

        /**
         * The user want to remove a group
         * we might need confirmation before proceeding.
         */
        removeUnitPriceGroup( group_fields, group ) {
            const hasIdField    =   group_fields.filter( field => field.name === 'id' && field.value !== undefined );
                Popup.show( nsPosConfirmPopupVue, {
                    title: __( 'Confirm Your Action' ),
                    message: __( 'Would you like to delete this group ?' ),
                    onAction: ( action ) => {
                        if ( action ) {
                            if ( hasIdField.length > 0 ) {
                                this.confirmUnitQuantityDeletion({ group_fields, group });
                            } else {
                                const index     =   group.indexOf( group_fields );
                                group.splice( index, 1 );
                            }
                        }
                    }
                });
        },

        confirmUnitQuantityDeletion({ group_fields, group }) {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Your Attention Is Required' ),
                size: 'w-3/4-screen h-2/5-screen',
                message: __( 'The current unit you\'re about to delete has a reference on the database and it might have already procured stock. Deleting that reference will remove procured stock. Would you proceed ?' ),
                onAction: ( action ) => {
                    if ( action ) {
                        const id    =   group_fields.filter( f => f.name === 'id' )
                        .map( f => f.value )[0];

                        nsHttpClient.delete( `/api/nexopos/v4/products/units/quantity/${id}`)
                            .subscribe( result => {
                                const index     =   group.indexOf( group_fields );
                                group.splice( index, 1 );
                                nsSnackBar.success( result.message ).subscribe();
                            }, ( error ) => {
                                nsSnackbar.error( error.message ).subscribe();
                            });
                    }
                }
            });
        },

        /**
         * When the user click on "New Group", 
         * this check if there is not enough options as there is groups
         */
        addUnitGroup( field ) {
            if ( field.options.length === 0 ) {
                return nsSnackBar.error( __( 'Please select at least one unit group before you proceed.' ) ).subscribe();
            }

            if( field.options.length > field.groups.length ) {
                field.groups.push(JSON.parse( JSON.stringify( field.fields ) ) );
            } else {
                nsSnackBar.error( __( 'There shoulnd\'t be more option than there are units.' ) ).subscribe();
            }
        },

        /**
         * When a change is made on unit group
         * we need to pull units attached to and make them available
         * for every groups. Validation should prevent duplicated units.
         */
        loadAvailableUnits( unit_section ) {
            nsHttpClient.get( this.unitsUrl.replace( '{id}', unit_section.fields.filter( f => f.name === 'unit_group' )[0].value ) )
                .subscribe( result => {

                    /**
                     * For each group, we'll loop to find
                     * the field that allow to choose the unit
                     * in order to change the options available
                     */
                    unit_section.fields.forEach( field => {
                        if ( field.type === 'group' ) {
                            field.options   =   result;
                            field.fields.forEach( _field => {
                                if ( _field.name === 'unit_id' ) {
                                    console.log( _field );
                                    _field.options  =   result.map( option => {
                                        return {
                                            label: option.name,
                                            value: option.id
                                        }
                                    });
                                }
                            })
                        }
                    });

                    this.$forceUpdate();
                })
        },

        /**
         * @deprecated
         */
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
                return nsSnackBar.error( this.$slots[ 'error-form-invalid' ] ? this.$slots[ 'error-form-invalid' ][0].text : __( 'Unable to proceed the form is not valid.' ) ).subscribe();
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
                return nsSnackBar.error( this.$slots[ 'error-multiple-primary' ] ? this.$slots[ 'error-multiple-primary' ][0].text : __( 'Unable to proceed, more than one product is set as primary' ) ).subscribe();
            }

            const validation        =   [];

            this.form.variations.map( ( v, i ) => {
                return v.tabs.units.fields
                    .filter( field => field.type === 'group' )
                    .forEach( fields_groups => {
                        const uniqueness    =   new Object;
                        fields_groups.groups.forEach( fields => {
                            validation.push( this.formValidation.validateFields( fields ) );                            
                        });
                });
            });

            if ( validation.length === 0 ) {
                return nsSnackBar.error( this.$slots[ 'error-no-units-groups' ] ? this.$slots[ 'error-no-units-groups' ][0].text : __( 'Either Selling or Purchase unit isn\'t defined. Unable to proceed.' ) ).subscribe(); 
            }

            if ( validation.filter( v => v === false ).length > 0 ) {
                this.$forceUpdate();
                return nsSnackBar.error( this.$slots[ 'error-invalid-unit-group' ] ? this.$slots[ 'error-invalid-unit-group' ][0].text : __( 'Unable to proceed as one of the unit group field is invalid' ) ).subscribe();
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

                    const groups    =   new Object;

                    v.tabs.units.fields.filter( field => field.type === 'group' )
                        .forEach( field => {
                            groups[ field.name ]    =   field.groups.map( fields => {
                                return this.formValidation.extractFields( fields );
                            })
                        });

                    data[ 'units' ]         =   { 
                        ...data[ 'units' ], 
                        ...groups
                    };

                    return data;
                })
            }
            
            this.formValidation.disableForm( this.form );

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

                    this.formValidation.enableForm( this.form );

                    if ( error.response ) {
                        this.formValidation.triggerError( this.form, error.response.data );
                    }
                })
        },
        deleteVariation( index ) {
            if ( confirm( this.$slots[ 'delete-variation' ] ? this.$slots[ 'delete-variation' ][0].text : __( 'Would you like to delete this variation ?' ) ) ) {
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

            /**
             * If the loaded tab is "units", we'll
             * load sub units based on the selection.
             */
            if ( activeIndex === 'units' ) {
                this.loadAvailableUnits( tabs[ activeIndex ] );
            }
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
        },

        removeImage( variation, group ) {
            const index     =   variation.tabs.images.groups.indexOf( group );
            variation.tabs.images.groups.splice( index, 1 );
        },
    },
    mounted() {
        this.loadForm();
    },
    name: 'ns-manage-products',
}
</script>