@extends( 'layout.dashboard' )


@section( 'layout.dashboard.footer' )
    @parent
<script type="module">
// const nsLabelsProductSettings   =   defineComponent({
//     props: [ 'popup' ],
//     template: `
//     <div>
//         <div class="shadow-lg ns-box w-95vw md:w-2/5-screen">
//             <div class="border-b ns-box-body p-2 flex justify-between items-center">
//                 <h3>{{ __( 'Settings' ) }}</h3>
//                 <div>
//                     <ns-close-button  @click="closePopup()"></ns-close-button>
//                 </div>
//             </div>
//             <div class="p-2">
//                 <ns-field :field="field" :key="index" :ref="field.name" v-for="(field, index) of fields"></ns-field>
//             </div>
//             <div class="border-t ns-box-footer p-2 flex justify-between">
//                 <div></div>
//                 <div>
//                     <ns-button type="info" @click="saveSettings()">{{ __( 'Save' ) }}</ns-button>
//                 </div>
//             </div>
//         </div>
//     </div>
//     `,
//     data() {
//         return {
//             fields: [],
//             validation: new FormValidation,
//         }
//     },
//     methods: {
//         __,
//         saveSettings() {
//             this.popup.close();
//             const form  =   this.validation.extractFields( this.fields );
//             this.popup.params.resolve( form )
//         },
//         closePopup() {
//             this.popup.close();
//             this.popup.params.reject( false );
//         }
//     },
//     mounted() {
//         const product       =   this.popup.params.product;

//         this.fields         =   this.validation.createFields([
//             {
//                 label: 'Unit',
//                 type: 'select',
//                 name: 'selectedUnitQuantity',
//                 description: 'Choose the unit to apply for the item',
//                 options: product.unit_quantities.map( unit_quantity => {
//                     return {
//                         label: unit_quantity.unit.name,
//                         value: unit_quantity
//                     }
//                 }),
//                 value: product.selectedUnitQuantity || product.unit_quantities[0]
//             }, {
//                 label: 'Unit',
//                 type: 'select',
//                 name: 'procurement_id',
//                 description: 'Choose quantity from procurement',
//                 options: product.unit_quantities.map( unit_quantity => {
//                     return {
//                         label: unit_quantity.unit.name,
//                         value: unit_quantity
//                     }
//                 }),
//                 value: product.procurement_id
//             }, {
//                 label: 'Quantity',
//                 type: 'number',
//                 name: 'times',
//                 description: 'Define how many time the product will be printed',
//                 value: product.times || 1
//             }
//         ]);

//         /**
//          * we want that the quantity field always select
//          * the entire quantity
//          */
//         setTimeout(() => {
//             console.log( this.$refs.times[0].$el );
//             this.$refs.times[0].$el.querySelector( 'input' ).addEventListener( 'focus', function( ) {
//                 this.select();
//             });
//         }, 100 );
//     }
// });

nsExtraComponents[ 'label-printing' ]   =   defineComponent({
    name: 'label-printing',
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
    computed: {
        form() {
            return ( new FormValidation ).extractFields( this.fields );
        },
        visibility() {
            return ( new FormValidation ).extractFields( this.visibilityFields );
        },
        itemsStyle() {
            return {
                padding: `${this.form.veritcal_padding || 0}px ${this.form.horizontal_padding || 0}px`
            }
        }
    },
    methods: {
        removeProduct( product ) {
            const index     =   this.products.indexOf( product );
            this.products.splice( index, 1 );
        },
        print() {
            const windowFeatures = "menubar=yes,location=yes,resizable=yes,scrollbars=yes,status=yes"

            if ( window.labelPrintPopup ) {
                window.labelPrintPopup.close();
            }
            // 
            window.labelPrintPopup     =   window.open( '', 'printPopup', windowFeatures );
            const styleOutput   =   Array.from( document.querySelectorAll( 'link' ) ).map( link => link.outerHTML ).join( "\n" )
            const paper         =   document.getElementById( 'label-printing-paper' );
            window.labelPrintPopup.document.writeln( `
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                ${styleOutput}
                <title>Printing Labels</title>
            </head>
            <body>
                ${paper.outerHTML}
                <script type="text/javascript">window.print()${'</'}script>
            </body>
            </html>
            ` );
            window.labelPrintPopup.document.writeln( paper.outerHTML );
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
                const result    =   ( await new Promise( ( resolve, reject ) => {
                    Popup.show( nsLabelsProductSettings, { product, resolve, reject });
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
                console.log( reference );
                this.itemsToPrint.push( ...reference );
            });
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
    }
})
</script>
@endsection

@section( 'layout.dashboard.body' )
<div class="h-full flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="flex-auto flex flex-col overflow-hidden" id="dashboard-content">
        <ns-print-label
            barcodeurl="{{ ns()->asset( 'storage/products/barcodes' ) }}"
            storename="{{ ns()->option->get( 'ns_store_name' ) }}">
        </ns-print-label>
    </div>
</div>
@endsection