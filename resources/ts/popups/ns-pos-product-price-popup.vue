<template>
    <div class="ns-box shadow-lg w-95vw md:w-3/5-screen lg:w-2/5-screen">
        <div class="popup-heading ns-box-header">
            <h3>{{ __( 'Product Price' ) }}</h3>
            <div>
                <ns-close-button @click="popupResolver( false )"></ns-close-button>
            </div>
        </div>
        <div class="flex flex-col ns-box-body">
            <div class="h-16 flex items-center justify-center elevation-surface info font-bold">
                <h2 class="text-2xl">{{ nsCurrency( product.unit_price ) }}</h2>
            </div>
            <ns-numpad :floating="true" @changed="updateProductPrice( $event )" @next="resolveProductPrice( $event )" :value="product.unit_price"></ns-numpad>
        </div>
    </div>
</template>
<script>
import { ref } from '@vue/reactivity';
import { nsNumpad, nsNumpadPlus } from '~/components/components';
import { nsCurrency } from '~/filters/currency';

export default {
    name: 'ns-pos-product-price-product',
    props: [ 'popup' ],
    components: {
        nsNumpad,
        nsNumpadPlus
    },
    computed: {
        // ...
    },
    data() {
        return {
            product: {},
            optionsSubscription: null,
            options: {},
            price: 0,
        }
    },
    mounted() {
        this.popupCloser();

        this.product    =   this.popup.params.product;

        this.optionsSubscription    =   POS.options.subscribe( options => {
            this.options    =   ref(options);
        });
    },
    beforeDestroy() {
        this.optionsSubscription.unsubscribe();
    },
    methods: {
        popupResolver,
        popupCloser,
        nsCurrency,
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