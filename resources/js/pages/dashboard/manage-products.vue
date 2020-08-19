
<template>
    <div class="form flex-auto flex flex-col" id="crud-form">
        <div v-if="Object.values( form ).length === 0" class="flex items-center justify-center flex-auto">
            <ns-spinner/>
        </div>
        <template v-if="Object.values( form ).length > 0">
            <div class="flex flex-col">
                <div class="flex justify-between items-center">
                    <label for="title" class="font-bold my-2 text-gray-700">{{ form.main.label }}</label>
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
                <div class="px-4 w-full">
                    <div id="tabbed-card" class="mb-8" :key="variation_index" v-for="(variation, variation_index) of form.variations">
                        <div id="card-header" class="flex flex-wrap justify-between">
                            <div class="flex flex-wrap">
                                <div @click="setTabActive( index, variation.tabs )" :class="tab.active ? 'bg-white' : 'bg-gray-100'" v-for="( tab, index ) in variation.tabs" v-bind:key="index" class="cursor-pointer text-gray-700 px-4 py-2 rounded-tl-lg rounded-tr-lg">
                                    {{ tab.label }}
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
                            <div class="-mx-4 flex flex-wrap">
                                <template  v-for="( field, index ) of getActiveTab( variation.tabs ).fields">
                                    <div :key="index" class="flex flex-col px-4 w-full md:w-1/2 lg:w-1/3" v-if="( variation_index === 0 && field.name !== 'name' ) || variation_index > 0">
                                        <ns-field :field="field"></ns-field>
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
import FormValidation from '../../libraries/form-validation'
import { nsSnackBar, nsHttpClient } from '../../bootstrap';

export default {
    data: () => {
        return {
            formValidation: new FormValidation,
            nsSnackBar,
            nsHttpClient,
            form: '',
        }
    },
    computed: {
        defaultVariation() {
            const newVariation     =   new Object;
            for( let tabIndex in this.form.variations[0].tabs ) {
                newVariation[ tabIndex ]            =   new Object;
                newVariation[ tabIndex ].label      =   this.form.variations[0].tabs[ tabIndex ].label;
                newVariation[ tabIndex ].active     =   this.form.variations[0].tabs[ tabIndex ].active;
                newVariation[ tabIndex ].fields     =   this.form.variations[0].tabs[ tabIndex ].fields
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
    props: [ 'submit-method', 'submit-url', 'return-link', 'src' ],
    methods: {
        submit() {
            this.formValidation.validateFields([ this.form.main ]);            
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
        parseForm( form ) {
            form.main.value     =   form.main.value === undefined ? '' : form.main.value;
            form.main           =   this.formValidation.createFields([ form.main ])[0];

            form.variations.forEach( ( variation, _index ) => {
                let index           =   0;
                for( let key in variation.tabs ) {

                    if ( index === 0 && variation.tabs[ key ].active === undefined ) {
                        variation.tabs[ key ].active  =   true;
                    }

                    variation.tabs[ key ].active    =   variation.tabs[ key ].active === undefined ? false : variation.tabs[ key ].active;
                    variation.tabs[ key ].fields    =   this.formValidation.createFields( variation.tabs[ key ].fields );

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
    },
    mounted() {
        this.loadForm();
    },
    name: 'ns-manage-products',
}
</script>