<template>
    <div @click="openDiscountPopup( order, 'cart' )" id="discount-button" class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center border-r border-box-edge flex-auto">
        <i class="mr-2 text-2xl lg:text-xl las la-percent"></i> 
        <span class="text-lg hidden md:inline lg:text-2xl">{{ __( 'Discount' ) }}</span>
    </div>
</template>
<script>
import nsPosDiscountPopupVue from '~/popups/ns-pos-discount-popup.vue';
export default {
    props: [ 'order', 'settings' ],
    methods: {
        __,
        openDiscountPopup( reference, type, productIndex = null ) {
            if ( ! this.settings.products_discount && type === 'product' ) {
                return nsSnackBar.error( __( `You're not allowed to add a discount on the product.` ) ).subscribe();
            }

            if ( ! this.settings.cart_discount && type === 'cart' ) {
                return nsSnackBar.error( __( `You're not allowed to add a discount on the cart.` ) ).subscribe();
            }

            Popup.show( nsPosDiscountPopupVue, { 
                reference,
                type,
                onSubmit( response ) {
                    if ( type === 'product' ) {
                        POS.updateProduct( reference, response, productIndex );
                    } else if ( type === 'cart' ) {
                        POS.updateCart( reference, response );
                    }
                }
            }, {
                popupClass: 'bg-white h:2/3 shadow-lg xl:w-1/4 lg:w-2/5 md:w-2/3 w-full'
            })
        },
    }
}
</script>