<template>
    <div id="discount-popup" class="ns-box shadow min-h-2/5-screen w-6/7-screen md:w-3/5-screen lg:w-3/5-screen xl:w-2/5-screen relative">
        <div class="flex-shrink-0 flex justify-between items-center p-2 border-b ns-box-header">
            <div>
                <h1 class="text-xl font-bold text-primary text-center" v-if="type === 'product'">{{ __( 'Product Discount' ) }}</h1>
                <h1 class="text-xl font-bold text-primary text-center" v-if="type === 'cart'">{{ __( 'Cart Discount' ) }}</h1>
            </div>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div id="screen" class="h-16 ns-box-body text-white flex items-center justify-center">
            <h1 class="font-bold text-3xl">
                <span v-if="mode === 'flat'">{{ nsCurrency( finalValue ) }}</span>
                <span v-if="mode === 'percentage'">{{ finalValue }}%</span>
            </h1>
        </div>
        <div id="switch-mode" class="flex">
            <button @click="setPercentageType('flat')" :class="mode === 'flat' ? 'bg-tab-active' : 'bg-tab-inactive text-tertiary'" class="outline-none w-1/2 py-2 flex items-center justify-center">{{ __( 'Flat' ) }}</button>
            <hr class="border-r border-box-edge">
            <button @click="setPercentageType('percentage')" :class="mode === 'percentage' ? 'bg-tab-active' : 'bg-tab-inactive text-tertiary'" class="outline-none w-1/2 py-2 flex items-center justify-center">{{ __( 'Percentage' ) }}</button>
        </div>
        <ns-numpad v-if="options.ns_pos_numpad === 'default'" :floating="options.ns_pos_allow_decimal_quantities"
                   @changed="updateValue( $event )" @next="resolveValue( $event )"
                   :value="rawValue"></ns-numpad>
        <ns-numpad-plus v-if="options.ns_pos_numpad === 'advanced'" @changed="updateValue( $event )"
                        @next="resolveValue( $event )" :value="rawValue"></ns-numpad-plus>
    </div>
</template>
<script>
import { nsCurrency } from '~/filters/currency';
import { __ } from '~/libraries/lang';
import popupCloser from "~/libraries/popup-closer";
import {nsNumpad, nsNumpadPlus} from "~/components/components";
import {ref} from "@vue/reactivity";
import NsCloseButton from "~/components/ns-close-button.vue";

export default {
    name: 'ns-pos-discount-popup',
    components: {NsCloseButton, nsNumpadPlus, nsNumpad},
    props: [ 'popup' ],
    data() {
        return {
            finalValue: 1,
            rawValue: 0,
            virtualStock: null,
            popupSubscription: null,
            mode: '',
            type: '',
            isLoading: false,
            optionsSubscription: null,
            options: {},
        }
    },
    mounted() {
        this.mode           =   this.popup.params.reference.discount_type || 'percentage';
        this.type           =   this.popup.params.type;

        if ( this.mode === 'percentage' ) {
            this.finalValue     =   this.popup.params.reference.discount_percentage || 1;
        } else {
            this.finalValue     =   this.popup.params.reference.discount || 1;
        }

        this.optionsSubscription = POS.options.subscribe(options => {
            this.options = ref(options);
        });

        this.popupCloser();
    },
    beforeUnmount() {
        this.optionsSubscription.unsubscribe();
    },
    methods: {
        __,
        nsCurrency,
        popupCloser,

        setPercentageType( mode ) {
            this.mode       =   mode;
        },
        closePopup() {
            this.popup.close();
        },

        updateValue(value) {
            this.rawValue = value;
            this.finalValue = parseFloat(value) || 0;
        },

        resolveValue(value) {
            this.popup.params.onSubmit({
                discount_type           :   this.mode,
                discount_percentage     :   this.mode === 'percentage' ? this.finalValue : undefined,
                discount                :   this.mode === 'flat' ? this.finalValue : undefined
            });
            this.popup.close();
        }
    }
}
</script>
