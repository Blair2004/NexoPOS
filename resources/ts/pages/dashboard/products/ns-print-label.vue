<template>
    <div class="flex flex-auto overflow-hidden">
        <div class="flex-auto overflow-y-scroll ns-scrollbar bg-gray-900 p-10">
            <div class="shadow-lg bg-white" id="label-printing-paper" :style="{ width: ( form.document_size + 'px' ) || 'auto' }">
                <div class="grid" :class="'grid-cols-' + ( form.max_columns || 1 )">
                    <div class="item border border-black" :style="itemsStyle" v-for="item of itemsToPrint">
                        <h3 class="font-bold text-black text-xl text-center" v-if="visibility.show_store_name">{{ storename }}</h3>
                        <div class="flex justify-between py-1" v-if="visibility.show_product_name">
                            <span>{{ __( 'Product' ) }}</span>
                            <span>{{ item.name }}</span>
                        </div>
                        <div class="flex justify-between py-1" v-if="visibility.show_product_name">
                            <span>{{ __( 'Unit' ) }}</span>
                            <span>{{ item.selectedUnitQuantity.unit.name }}</span>
                        </div>
                        <div class="flex justify-between py-1" v-if="visibility.show_barcode_text">
                            <span>{{ __( 'Barcode' ) }}</span>
                            <span>{{ item.selectedUnitQuantity.barcode }}</span>
                        </div>
                        <div class="flex justify-between py-1" v-if="visibility.show_product_price">
                            <span>{{ __( 'Price' ) }}</span>
                            <span>{{ nsCurrency( item.selectedUnitQuantity.sale_price ) }}</span>
                        </div>
                        <div class="flex justify-center flex-col py-1">
                            <img :style="{ height: form.barcode_height + 'px' }" :src="barcodeurl + '/' + item.selectedUnitQuantity.barcode + '.png'" :alt="item.selectedUnitQuantity.barcode">
                            <div class="flex justify-center w-full">
                                <span class="-mt-4 bg-white inline-block p-1">{{ item.selectedUnitQuantity.barcode }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-1/4 p-4 flex-shrink-0 overflow-y-scroll ns-scrollbar">
            <div>
                <div class="shadow ns-box mb-4">
                    <div class="header border-b ns-box-header p-2">
                        <h3 class="font-semibold">{{ __( 'Products' ) }}</h3>
                    </div>
                    <div class="body p-2">
                        <div class="input-group info rounded border-2">
                            <input v-model="search_product" class=" w-full p-2" :placeholder="__( 'Search Products...' )"/>
                        </div>
                        <div class="h-0 relative anim-duration-300 fade-in-entrance" v-if="resultSuggestions.length > 0">
                            <ul class="shadow-lg ns-vertical-menu absolute w-full z-10">
                                <li @click="addProduct( product )" v-for="product of resultSuggestions" class="border p-2 flex flex-col cursor-pointer" style="margin-bottom:-1px;">
                                    <span class="font-semibold">{{ product.name }}</span>
                                    <span class="text-xs">{{ product.barcode }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="flex flex-col" v-if="products.length > 0">
                            <h3 class="font-semibold">{{ __( 'Included Products' ) }}</h3>
                            <ul>
                                <li v-for="product of products" :key="product.id" class="border border-box-elevation-edge bg-box-elevation-background p-2 flex justify-between items-center" style="margin-bottom:-1px;">
                                    <p class="flex flex-col">
                                        <span class="font-semibold">{{ product.name }} ({{ product.selectedUnitQuantity.unit.name }}) x{{ product.times }}</span>
                                        <span class="text-xs">{{ product.selectedUnitQuantity.barcode }}</span>
                                    </p>
                                    <div class="flex items-center">
                                        <p class="flex flex-col">
                                            <button @click="editProduct( product )" class="rounded-full flex h-6 w-6 items-center bg-success-primary text-white justify-center">
                                                <i class="las la-cog"></i>
                                            </button>
                                        </p>
                                        <div>
                                            <span @click="removeProduct( product )" class="ml-1 cursor-pointer bg-error-primary text-white rounded-full h-6 w-6 flex items-center justify-center font-bold">
                                                <i class="las la-times"></i>
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="border-t ns-box-footer p-2 flex justify-between">
                        <ns-button @click="print()" type="success"><i class="las la-print"></i></ns-button>
                        <ns-button @click="applySettings()" type="info">{{ __( 'Apply Settings' ) }}</ns-button>
                    </div>
                </div>
                <div class="shadow ns-box mb-4">
                    <div class="header border-b ns-box-header p-2">
                        <h3 class="font-semibold">{{ __( 'Basic Settings' ) }}</h3>
                    </div>
                    <div class="body p-2 ns-box-body">
                        <ns-field :field="field" v-for="(field, index) of fields" :key="index"></ns-field>
                    </div>
                </div>
                <div class="shadow ns-box mb-4">
                    <div class="header border-b ns-box-header p-2">
                        <h3 class="font-semibold">{{ __( 'Visibility Settings' ) }}</h3>
                    </div>
                    <div class="body p-2 ns-box-body">
                        <ns-field :field="field" v-for="(field, index) of visibilityFields" class="mb-2" :key="index"></ns-field>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { __ } from '~/libraries/lang';
import { defineComponent } from "vue";
import nsPrintLabelSettings from "./ns-print-label-settings.vue";

declare const Popup, nsEvent, nsHttpClient, nsSnackBar, FormValidation, nsCurrency;

export default defineComponent({
    name: 'ns-print-label',
    props: {
        // ...
        barcodeurl: {
            type: String,
            required: true,
        },
        storename: {
            type: String,
            required: true
        }
    },
    computed: {
        form() {
            return ( new FormValidation ).extractFields( this.fields );
        },
        visibility() {
            return ( new FormValidation ).extractFields( this.visibilityFields );
        },
        itemsStyle() {
            return {
                padding: `${this.form.veritcal_padding || 0}px ${this.form.horizontal_padding || 0}px`,
            }
        }
    },
    data() {
        return {
            product_field: [
                {
                    label: 'Product Field',
                    name: 'product_barcode',
                    description: 'Load Product By barcode'
                }
            ],
            resultSuggestions: [],
            fields: [],
            search_product: '',
            searchTimer: null,
            products: [],
            itemsToPrint: [],
            visibilityFields: [],
            printingPopup: null,
        }
    },
    watch: {
        search_product() {
            if ( this.search_product.length > 0 ) {
                clearTimeout( this.searchTimer );
                this.searchTimer    =   setTimeout( () => {
                    this.searchProduct()
                }, 500 );
            } else {
                this.resultSuggestions      =   [];
            }
        }
    },
    mounted() {
        const validation    =   new FormValidation;
        this.fields         =   validation.createFields([
            {
                type: 'select',
                label: 'Items Per Row',
                name: 'max_columns',
                value: 1,
                options: ( new Array(6) )
                    .fill( '' )
                    .map( ( _, index ) => {
                        return {
                            label: index +1,
                            value: index +1,
                        }
                    })
            }, {
                type: 'number',
                label: 'Vertical Padding (pixels)',
                name: 'veritcal_padding',
            }, {
                type: 'number',
                label: 'Horizontal Padding (pixels)',
                name: 'horizontal_padding',
            }, {
                type: 'number',
                label: 'Barcode Height (pixels)',
                name: 'barcode_height',
                value: 30
            }, {
                type: 'number',
                label: 'Document Size (pixels)',
                name: 'document_size',
                value: ''
            }
        ]);

        this.visibilityFields   =   [
            {
                type: 'checkbox',
                label: 'Show Store Name',
                name: 'show_store_name',
                value: true,
            }, {
                type: 'checkbox',
                label: 'Show Barcode Text',
                name: 'show_barcode_text',
                value: true,
            }, {
                type: 'checkbox',
                label: 'Show Product Price',
                name: 'show_product_price',
                value: true,
            }, {
                type: 'checkbox',
                label: 'Show Product Name',
                name: 'show_product_name',
                value: true,
            }, 
        ]
    },
    methods: {
        __,
        nsCurrency,
        removeProduct( product ) {
            const index     =   this.products.indexOf( product );
            this.products.splice( index, 1 );
        },
        print() {
            const windowFeatures = "menubar=yes,location=yes,resizable=yes,scrollbars=yes,status=yes"

            if ( this.printingPopup ) {
                this.printingPopup.close();
            }
            // 
            this.printingPopup     =   window.open( '', 'printPopup', windowFeatures );
            const styleOutput   =   Array.from( document.querySelectorAll( 'link' ) ).map( link => link.outerHTML ).join( "\n" )
            const paper         =   document.getElementById( 'label-printing-paper' );
            this.printingPopup.document.writeln( `
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                ${styleOutput}
                <title>Printing Labels</title>
                <style>
                    body {   
                        width: ${this.form.document_size + 'px' || 'auto'}
                    }
                </style>
            </head>
            <body>
                ${paper.outerHTML}
                <script type="text/javascript">window.print()${'</'}script>
            </body>
            </html>
            ` );
            this.printingPopup.document.writeln( paper.outerHTML );
        },
        async addProduct( product ) {
            try {
                await this.editProduct( product );   

                this.resultSuggestions      =   [];
                this.search_product         =   '';

                this.products.push( product );

            } catch( exception ) {
                console.log( exception );
            }
        },

        async editProduct( product ) {
            return new Promise( async ( resolve, reject ) => {
                const result:any    =   ( await new Promise( ( resolve, reject ) => {
                    Popup.show( nsPrintLabelSettings, { product, resolve, reject });
                }) );

                product.selectedUnitQuantity    =   result.selectedUnitQuantity;
                product.times                   =   result.times;

                this.$forceUpdate();

                return resolve( product );
            })
        },

        searchProduct() {
            nsHttpClient.post( `/api/products/search`, { search: this.search_product })
                .subscribe( result => {
                    this.resultSuggestions      =   result;
                }, ( error ) => {
                    nsSnackBar.error( error.message ).subscribe();
                })
        },

        applySettings() {
            this.itemsToPrint   =   [];
            this.products.forEach( product => {
                const reference     =   ( new Array( parseInt( product.times ) ) )
                    .fill( '' )
                    .map( _ => product );
                this.itemsToPrint.push( ...reference );
            });
        }
    }
})

</script>