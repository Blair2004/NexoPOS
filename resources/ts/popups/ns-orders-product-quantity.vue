<template>
    <div class="shadow-xl ns-box overflow-hidden w-95vw md:w-4/6-screen lg:w-3/7-screen">
        <div class="p-2 flex justify-between ns-box-header">
            <h3 class="font-semibold">{{ __( 'Quantity' ) }}</h3>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div v-if="product" class="border-t border-b ns-box-body py-2 flex items-center justify-center text-2xl font-semibold">
            <span>{{ seeValue }}</span> 
            <span class="text-primary text-sm">({{ availableQuantity }} {{ __( 'available' ) }})</span>
        </div>
        <div class="flex-auto overflow-y-auto p-2" v-if="product">
            <ns-numpad :value="product.quantity" @next="updateQuantity( $event )" @changed="setChangedValue( $event )"></ns-numpad>
        </div>
    </div>
</template>
<script>
import popupResolver from "~/libraries/popup-resolver";
import nsNumpad from "~/components/ns-numpad.vue";
import { nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
export default {
    components: {
        nsNumpad
    },
    props: [ 'popup' ],
    data() {
        return {
            product: null,
            seeValue: 0,
            availableQuantity: 0
        }
    },
    mounted() {
        this.product            =   this.popup.params.product;
        this.availableQuantity  =   this.popup.params.availableQuantity;
        this.seeValue           =   this.product.quantity;
    },
    methods: {
        __,
        
        popupResolver,

        close() {
            this.popup.params.reject( false );
            this.popup.close();
        },

        setChangedValue( quantity ) {
            this.seeValue   =   quantity;
        },

        updateQuantity( quantity ) {
            if ( quantity > this.availableQuantity ) {
                return nsSnackBar.error( 'Unable to proceed as the quantity provided is exceed the available quantity.' ).subscribe();
            }

            this.product.quantity   =   parseFloat( quantity );
            this.popup.params.resolve( this.product );
            this.popup.close();
        },        
    }
}
</script>