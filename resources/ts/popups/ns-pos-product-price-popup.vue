<template>
    <div class="bg-white shadow-lg w-95vw md:w-3/5-screen lg:w-2/5-screen">
        <div class="popup-heading">
            <h3>{{ __( 'Product Price' ) }}</h3>
            <div>
                <ns-close-button @click="popupResolver( false )"></ns-close-button>
            </div>
        </div>
        <div class="flex flex-col">
            <div class="h-16 flex items-center justify-center bg-gray-800 text-white font-bold">
                <h2 class="text-2xl">{{ value | currency }}</h2>
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
        product() {
            return this.$popupParams.product;
        }
    },
    data() {
        return {
            value: 0
        }
    },
    mounted() {
        this.value      =   this.product.unit_price;
        this.popupCloser();
    },
    methods: {
        popupResolver,
        popupCloser,
        __,

        updateProductPrice( price ) {
            this.value  =   price;
        },

        resolveProductPrice( price ) {
            this.popupResolver( price );
        }
    }
}
</script>