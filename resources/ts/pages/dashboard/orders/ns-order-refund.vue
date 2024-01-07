<template>
    <div class="-m-4 flex-auto flex flex-wrap relative">
        <div v-if="isSubmitting" class="bg-overlay h-full w-full flex items-center justify-center absolute z-30">
            <ns-spinner></ns-spinner>
        </div>
        <div class="px-4 w-full lg:w-1/2">
            <h3 class="py-2 border-b-2 text-primary border-info-primary">{{ __( 'Refund With Products' ) }}</h3>
            <div class="my-2">
                <ul>
                    <li class="border-b border-info-primary flex justify-between items-center mb-2">
                        <div class="flex-auto flex-col flex">
                            <div class="p-2 flex">
                                <ns-field v-for="(field,index) of selectFields" :field="field" :key="index"></ns-field>
                            </div>
                            <div class="flex justify-between p-2">
                                <div class="flex items-center text-primary">
                                    <span v-if="order.shipping > 0" class="mr-2">{{ __( 'Refund Shipping' ) }}</span>
                                    <ns-checkbox v-if="order.shipping > 0" @change="toggleRefundShipping( $event )" :checked="refundShipping"></ns-checkbox>
                                </div>
                                <div>
                                    <button @click="addProduct()" class="border rounded-full px-2 py-1 ns-inset-button info">{{ __( 'Add Product' ) }}</button>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <h4 class="py-1 border-b-2 text-primary border-info-primary">{{ __( 'Products' ) }}</h4>
                    </li>
                    <li v-for="product of refundables" :key="product.id" class="elevation-surface border flex justify-between items-center mb-2">
                        <div class="px-2 text-primary flex justify-between flex-auto">
                            <div class="flex flex-col">
                                <p class="py-2">
                                    <span>{{ product.name }}</span>
                                    <span v-if="product.condition === 'damaged'" class="rounded-full px-2 py-1 text-xs bg-error-tertiary mx-2 text-white">{{ __( 'Damaged' ) }}</span>
                                    <span v-if="product.condition === 'unspoiled'" class="rounded-full px-2 py-1 text-xs bg-success-tertiary mx-2 text-white">{{ __( 'Unspoiled' ) }}</span>
                                </p>
                                <small>{{ product.unit.name }}</small>
                            </div>
                            <div class="flex items-center justify-center">
                                <span class="py-1 flex items-center cursor-pointer border-b border-dashed border-info-primary">{{ nsCurrency( product.unit_price * product.quantity ) }}</span>
                            </div>
                        </div>
                        <div class="flex">
                            <p @click="openSettings( product )" class="p-2 border-l border-info-primary cursor-pointer text-primary ns-numpad-key w-16 h-16 flex items-center justify-center">
                                <i class="las la-cog text-xl"></i>
                            </p>
                            <p @click="deleteProduct( product )" class="p-2 border-l border-info-primary cursor-pointer text-primary ns-numpad-key w-16 h-16 flex items-center justify-center">
                                <i class="las la-trash"></i>
                            </p>
                            <p @click="changeQuantity( product )" class="p-2 border-l border-info-primary cursor-pointer text-primary ns-numpad-key w-16 h-16 flex items-center justify-center">{{ product.quantity }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="px-4 w-full lg:w-1/2">
            <h3 class="py-2 border-b-2 text-primary border-info-primary">{{ __( 'Summary' ) }}</h3>
            <div class="py-2">
                <div class="elevation-surface border font-semibold flex mb-2 p-2 justify-between">
                    <span>{{ __( 'Total' ) }}</span>
                    <span>{{ nsCurrency( total ) }}</span>
                </div>
                <div class="elevation-surface border success font-semibold flex mb-2 p-2 justify-between">
                    <span>{{ __( 'Paid' ) }}</span>
                    <span>{{ nsCurrency( order.tendered ) }}</span>
                </div>
                <div @click="selectPaymentGateway()" class="elevation-surface border info font-semibold flex mb-2 p-2 justify-between cursor-pointer">
                    <span>{{ __( 'Payment Gateway' ) }}</span>
                    <span>{{ selectedPaymentGateway ? selectedPaymentGatewayLabel : 'N/A' }}</span>
                </div>
                <div class="elevation-surface border font-semibold flex mb-2 p-2 justify-between">
                    <span>{{ __( 'Screen' ) }}</span>
                    <span>{{ nsCurrency( screen ) }}</span>
                </div>
                <div>
                    <ns-numpad-plus :currency="true" @changed="updateScreen( $event )" :value="screen" @next="proceedPayment( $event )"></ns-numpad-plus>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import FormValidation from '~/libraries/form-validation';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import nsOrdersRefundProducts from "~/popups/ns-orders-refund-product-popup.vue";
import nsOrdersProductQuantityVue from '~/popups/ns-orders-product-quantity.vue';
import nsSelectPopupVue from '~/popups/ns-select-popup.vue';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import { Popup } from '~/libraries/popup';
import { __ } from '~/libraries/lang';
import Print from '~/libraries/print';
import { nsNumpad } from '~/components/components';
import { nsCurrency } from '~/filters/currency';

export default {
    components: {
        nsNumpad
    },
    props: [ 'order' ],
    computed: {
        total() {
            if ( this.refundables.length > 0 ) {
                return this.refundables.map( product => parseFloat( product.unit_price ) * parseFloat( product.quantity ) )
                    .reduce( ( before, after ) => before + after ) + this.shippingFees;
            }

            return 0 + this.shippingFees;
        },
        shippingFees() {
            return ( this.refundShipping ? this.order.shipping : 0 );
        }
    },
    data() {
        return {
            isSubmitting: false,
            formValidation: new FormValidation,
            refundables: [],
            paymentOptions: [],
            paymentField: [],
            print: new Print({ urls: systemUrls, options: systemOptions }),
            refundShipping: false,
            selectedPaymentGateway: false,
            screen: 0,
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
                    label: __( 'Product' ),
                    description: __( 'Select the product to perform a refund.' )
                }
            ]
        }
    }, 
    methods: {
        __,
        nsCurrency,
        
        updateScreen( value ) {
            this.screen     =   value;
        },

        toggleRefundShipping( event ) {
            this.refundShipping     =   event;

            if ( this.refundShipping ) {

            }
        },

        proceedPayment() {
            if ( this.selectedPaymentGateway === false ) {
                return nsSnackBar.error( __( 'Please select a payment gateway before proceeding.' ) ).subscribe();
            }

            if ( this.total === 0 ) {
                return nsSnackBar.error( __( 'There is nothing to refund.' ) ).subscribe();
            }

            if ( this.screen === 0 ) {
                return nsSnackBar.error( __( 'Please provide a valid payment amount.' ) ).subscribe();
            }

            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: __( 'The refund will be made on the current order.' ),
                onAction: ( action ) => {
                    if ( action ) {
                        this.doProceed();
                    }
                }
            });
        },

        doProceed() {
            const data      =   {
                products            :   this.refundables,
                total               :   this.screen,
                payment             :   { identifier: this.selectedPaymentGateway },
                refund_shipping     :   this.refundShipping
            }

            this.isSubmitting   =   true;
            nsHttpClient.post( `/api/orders/${this.order.id}/refund`, data )
                .subscribe({
                    next:  (result) => {
                        this.isSubmitting   =   false;
                        
                        this.$emit( 'changed', true );
                        
                        /**
                         * if the order is fully refunded
                         * we should ask the parent component to switch
                         * the active tab to "details" which is the default main tab.
                         */
                        if ( result.data.order.payment_status === 'refunded' ) {
                            this.$emit( 'loadTab', 'details' );
                        }   

                        this.print.process( result.data.orderRefund.id, 'refund' );
                        
                        nsSnackBar.success( result.message ).subscribe();
                    },
                    error: (error) => {
                        this.isSubmitting   =   false;
                        nsSnackBar.error( error.message ).subscribe();
                    }
                })
        },

        addProduct() {
            this.formValidation.validateFields( this.selectFields );

            if ( ! this.formValidation.fieldsValid( this.selectFields ) ) {
                return nsSnackBar.error( __( 'Please select a product before proceeding.' ) ).subscribe();
            }

            const fields                =   this.formValidation.extractFields( this.selectFields );
            const currentProduct        =   this.order.products.filter( product => product.id === fields.product_id );
            const existingProducts      =   this.refundables.filter( product => product.id === fields.product_id );

            if ( existingProducts.length > 0 ) {
                const totalUsedQuantity     =   existingProducts
                    .map( product => parseInt( product.quantity ) )
                    .reduce( ( before, after ) => before + after );

                if ( totalUsedQuantity === currentProduct[0].quantity ) {
                    return nsSnackBar.error( __( 'Not enough quantity to proceed.' ) ).subscribe();
                }
            }

            if ( currentProduct[0].quantity === 0 ) {
                return nsSnackBar.error( __( 'Not enough quantity to proceed.' ) ).subscribe();
            }

            const product    =   { ...currentProduct[0], ...{
                condition: '',
                description: '',
            }};

            const promise   =   new Promise( ( resolve, reject ) => {
                Popup.show( nsOrdersRefundProducts, { resolve, reject, product })
            });

            promise.then( product => {
                /**
                 * this will set the quantity to be equal to the 
                 * remaining available quantity
                 */
                product.quantity    =   this.getProductOriginalQuantity( product.id ) - this.getProductUsedQuantity( product.id );
                this.refundables.push( product );
            }, _ => _ )
        },

        getProductOriginalQuantity( product_id ) {
            const product   =   this.order.products.filter( product => product.id === product_id );
            
            if ( product.length > 0 ) {
                return product
                    .map( product => parseFloat( product.quantity ) )
                    .reduce( ( before, after ) => before + after );
            }

            return 0;
        },

        getProductUsedQuantity( product_id ) {
            const existingProducts      =   this.refundables.filter( product => product.id === product_id );

            if ( existingProducts.length > 0 ) {
                const totalUsedQuantity     =   existingProducts
                    .map( product => parseFloat( product.quantity ) )
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
            }, _ => _ );
        },

        async selectPaymentGateway() {
            try {
                this.selectedPaymentGateway     =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsSelectPopupVue, { resolve, reject, value : [ this.selectedPaymentOption ], ...this.paymentField[0] })
                });

                this.selectedPaymentGatewayLabel   =   this.paymentField[0].options
                    .filter( option => option.value === this.selectedPaymentGateway )[0].label;
            } catch( exception ) {
                nsSnackBar.error( __( 'An error has occured while seleting the payment gateway.' ) ).subscribe();
            }
        },

        changeQuantity( product ) {
            const promise   =   new Promise( ( resolve, reject ) => {
                const availableQuantity     =   
                    this.getProductOriginalQuantity( product.id ) - this.getProductUsedQuantity( product.id ) + parseFloat( product.quantity );
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
        },

        deleteProduct( product ) {
            const promise   =   new Promise( ( resolve, reject ) => {
                Popup.show( nsPosConfirmPopupVue, {
                    title: __( 'Confirm Your Action' ),
                    message: __( 'Would you like to delete this product ?' ),
                    onAction: action => {
                        if ( action ) {
                            const index     =   this.refundables.indexOf( product );
                            this.refundables.splice( index, 1 );
                        }
                    }
                });
            })
        }
    },  
    mounted() {
        this.selectFields   =   this.formValidation.createFields( this.selectFields );
        nsHttpClient.get( '/api/orders/payments' )
            .subscribe( paymentField => {
                this.paymentField       =   paymentField;
            });
    }
}
</script>