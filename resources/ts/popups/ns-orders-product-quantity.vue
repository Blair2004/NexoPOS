<template>
    <div class="shadow-xl bg-white overflow-hidden w-95vw md:w-4/6-screen lg:w-3/7-screen">
        <div class="p-2 flex justify-between">
            <h3 class="font-semibold">Quantity</h3>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div v-if="product" class="border-t border-b border-gray-200 py-2 flex items-center justify-center text-2xl font-semibold">
            <span>{{ seeValue }}</span> 
            <span class="text-gray-600 text-sm">({{ availableQuantity }} available)</span>
        </div>
        <div class="flex-auto overflow-y-auto p-2" v-if="product">
            <ns-numpad :value="product.quantity" @next="updateQuantity( $event )" @changed="setChangedValue( $event )"></ns-numpad>
        </div>
    </div>
</template>
<script>
import popupResolver from "@/libraries/popup-resolver";
import nsNumpad from "@/components/ns-numpad";
import { nsSnackBar } from '@/bootstrap';
export default {
    components: {
        nsNumpad
    },
    data() {
        return {
            product: null,
            seeValue: 0,
            availableQuantity: 0
        }
    },
    mounted() {
        this.product            =   this.$popupParams.product;
        this.availableQuantity  =   this.$popupParams.availableQuantity;
        this.seeValue           =   this.product.quantity;
    },
    methods: {
        popupResolver,

        close() {
            this.$popupParams.reject( false );
            this.$popup.close();
        },

        setChangedValue( quantity ) {
            this.seeValue   =   quantity;
        },

        updateQuantity( quantity ) {
            if ( quantity > this.availableQuantity ) {
                return nsSnackBar.error( 'Unable to proceed as the quantity provided is exceed the available quantity.' ).subscribe();
            }

            this.product.quantity   =   parseFloat( quantity );
            this.$popupParams.resolve( this.product );
            this.$popup.close();
        },        
    }
}
</script>