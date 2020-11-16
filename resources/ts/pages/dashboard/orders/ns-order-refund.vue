<template>
    <div class="-mx-4 flex flex-wrap">
        <div class="px-4 w-full lg:w-1/2">
            <h3 class="py-2 border-b-2 text-gray-700 border-blue-400">Refund With Products</h3>
            <div class="my-2">
                <ul>
                    <li class="border-b border-blue-400 flex justify-between items-center mb-2">
                        <div class="flex-auto flex-col flex">
                            <div class="p-2 flex">
                                <ns-field v-for="(field,index) of selectFields" :field="field" :key="index"></ns-field>
                            </div>
                            <div class="flex justify-end p-2">
                                <button @click="addProduct()" class="border-2 rounded-full border-gray-200 px-2 py-1 hover:bg-blue-400 hover:text-white text-gray-700">Add Product</button>
                            </div>
                        </div>
                    </li>
                    <li>
                        <h4 class="py-1 border-b-2 text-gray-700 border-blue-400">Products</h4>
                    </li>
                    <li v-for="product of refundables" :key="product.id" class="bg-gray-100 border-b border-blue-400 flex justify-between items-center mb-2">
                        <div class="px-2 text-gray-700 flex justify-between flex-auto">
                            <div class="flex flex-col">
                                <p class="py-2">
                                    <span>{{ product.name }}</span>
                                    <span v-if="product.return_condition === 'damaged'" class="rounded-full px-2 py-1 text-xs bg-red-400 mx-2 text-white">Damaged</span>
                                    <span v-if="product.return_condition === 'unspoiled'" class="rounded-full px-2 py-1 text-xs bg-green-400 mx-2 text-white">Unspoiled</span>
                                </p>
                                <small>{{ product.unit.name }}</small>
                            </div>
                            <div class="flex items-center justify-center">
                                <span class="py-1 flex items-center cursor-pointer border-b border-dashed border-blue-400">{{ product.unit_price * product.quantity | currency }}</span>
                            </div>
                        </div>
                        <div class="flex">
                            <p @click="openSettings( product )" class="p-2 border-l border-blue-400 cursor-pointer text-gray-600 hover:bg-blue-100 w-16 h-16 flex items-center justify-center">
                                <i class="las la-cog text-xl"></i>
                            </p>
                            <p @click="changeQuantity( product )" class="p-2 border-l border-blue-400 cursor-pointer text-gray-600 hover:bg-blue-100 w-16 h-16 flex items-center justify-center">{{ product.quantity }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="px-4 w-full lg:w-1/2">
            <h3 class="py-2 border-b-2 text-gray-700 border-blue-400">Refund Without Products</h3>
        </div>
    </div>
</template>
<script>
import FormValidation from '@/libraries/form-validation';
import { nsSnackBar } from '@/bootstrap';
import nsOrdersRefundProducts from "@/popups/ns-orders-refund-product-popup";
import nsOrdersProductQuantityVue from '@/popups/ns-orders-product-quantity.vue';

export default {
    props: [ 'order' ],
    data() {
        return {
            formValidation: new FormValidation,
            refundables: [],
            selectFields: [
                {
                    type: 'select',
                    options: this.order.products.map( product => {
                        return {
                            label: `${product.name} - ${product.unit.name} (x${product.quantity})`,
                            value: product.id
                        }
                    }),
                    validation: 'required',
                    name: 'product_id',
                    label: 'Product',
                    description: 'Select the product to perform a refund.'
                }
            ]
        }
    }, 
    methods: {
        addProduct() {
            this.formValidation.validateFields( this.selectFields );

            if ( ! this.formValidation.fieldsValid( this.selectFields ) ) {
                return nsSnackBar.error( 'Please select a product before proceeding.' ).subscribe();
            }

            const fields                =   this.formValidation.extractFields( this.selectFields );
            const currentProduct        =   this.order.products.filter( product => product.id === fields.product_id );
            const existingProducts      =   this.refundables.filter( product => product.id === fields.product_id );

            if ( existingProducts.length > 0 ) {
                const totalUsedQuantity     =   existingProducts
                    .map( product => parseInt( product.quantity ) )
                    .reduce( ( before, after ) => before + after );

                if ( totalUsedQuantity === currentProduct[0].quantity ) {
                    return nsSnackBar.error( 'Not enough quantity to proceed.' ).subscribe();
                }
            }

            const product    =   { ...currentProduct[0] };

            const promise   =   new Promise( ( resolve, reject ) => {
                Popup.show( nsOrdersRefundProducts, { resolve, reject, product })
            });

            promise.then( result => {
                this.refundables.push( result );
            })
        },

        getProductUsedQuantity( product_id ) {
            const existingProducts      =   this.refundables.filter( product => product.id === product_id );

            if ( existingProducts.length > 0 ) {
                const totalUsedQuantity     =   existingProducts
                    .map( product => parseInt( product.quantity ) )
                    .reduce( ( before, after ) => before + after );

                return totalUsedQuantity;
            }

            return 0;
        },

        openSettings( product ) {
            const promise   =   new Promise( ( resolve, reject ) => {
                Popup.show( nsOrdersRefundProducts, { resolve, reject, product })
            });

            promise.then( _updatedProduct => {
                const productIndex  =   this.refundables.indexOf( product );
                this.$set( this.refundables, productIndex, _updatedProduct );
            });
        },

        changeQuantity( product ) {
            const promise   =   new Promise( ( resolve, reject ) => {
                const availableQuantity     =   this.getProductUsedQuantity( product.id ) - product.quantity;
                Popup.show( nsOrdersProductQuantityVue, { resolve, reject, product, availableQuantity });
            });

            promise.then( updatedProduct => {
                /**
                 * we do exclude the product as we don't want that 
                 * to be counted as a used quantity.
                 */
                if ( updatedProduct.quantity > this.getProductUsedQuantity( product.id ) - product.quantity ) {
                    const productIndex  =   this.refundables.indexOf( product );
                    this.$set( this.refundables, productIndex, updatedProduct );
                }
            });
        }
    },  
    mounted() {
        this.selectFields   =   this.formValidation.createFields( this.selectFields );
    }
}
</script>