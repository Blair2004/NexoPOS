<template>
    <div class="ns-box shadow-lg w-95vw md:w-3/5-screen lg:w-2/5-screen">
        <div class="popup-heading ns-box-header">
            <h3>{{ __( 'Product Price' ) }}</h3>
            <div>
                <ns-close-button @click="popupResolver( false )"></ns-close-button>
            </div>
        </div>
        <div class="flex flex-col ns-box-body">
            <div class="h-16 flex items-center justify-center elevation-surface font-bold">
                <h2 class="text-2xl">{{ product.unit_price | currency }}</h2>
            </div>
            <div class="p-2">
                <ns-numpad 
                    @changed="updateProductPrice( $event )"
                    @next="resolveProductPrice( $event )" 
                    :floating="true"
                    :value="product.unit_price"></ns-numpad>
            </div>
        </div>
    </div>
</template>
<script>
import nsNumpad from "@/components/ns-numpad.vue";
export default {
    name: 'ns-pos-product-price-product',
    components: {
        nsNumpad
    },
    computed: {
        // ...
    },
    data() {
        return {
            product: {}
        }
    },
    mounted() {
        this.popupCloser();

        this.product    =   this.$popupParams.product;
    },
    methods: {
        popupResolver,
        popupCloser,
        __,

        updateProductPrice( price ) {
            this.product.unit_price     =   price;
        },

        resolveProductPrice( price ) {
            this.popupResolver( this.product.unit_price );
        }
    }
}
</script>