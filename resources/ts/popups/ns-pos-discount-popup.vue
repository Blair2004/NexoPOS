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
        <div id="numpad" class="grid grid-flow-row grid-cols-3 grid-rows-3">
            <div 
                @click="inputValue( key )"
                :key="index" 
                v-for="(key,index) of keys" 
                class="text-primary ns-numpad-key info text-xl font-bold border h-24 flex items-center justify-center cursor-pointer">
                <span v-if="key.value !== undefined">{{ key.value }}</span>
                <i v-if="key.icon" class="las" :class="key.icon"></i>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { nsCurrency } from '~/filters/currency';
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';

export default {
    name: 'ns-pos-discount-popup',
    props: [ 'popup' ],
    data() {
        return {
            finalValue: 1,
            virtualStock: null,
            popupSubscription: null,
            mode: '',
            type: '',
            allSelected: true,
            isLoading: false,
            keys: [
                ...([7,8,9].map( key => ({ identifier: key, value: key }))),
                ...([4,5,6].map( key => ({ identifier: key, value: key }))),
                ...([1,2,3].map( key => ({ identifier: key, value: key }))),
                ...[{ identifier: 'backspace', icon : 'la-backspace' },{ identifier: 0, value: 0 }, { identifier: 'next', icon: 'la-share' }],
            ]
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

        this.popupCloser();
    },
    methods: {
        __,
        nsCurrency,
        popupResolver,
        popupCloser,
        
        setPercentageType( mode ) {
            this.mode       =   mode;
        },
        closePopup() {
            this.popup.close();
        },

        inputValue( key ) {
            if ( key.identifier === 'next' ) {
                this.popup.params.onSubmit({
                    discount_type           :   this.mode,
                    discount_percentage     :   this.mode === 'percentage' ? this.finalValue : undefined,
                    discount                :   this.mode === 'flat' ? this.finalValue : undefined
                });
                this.popup.close();
            } else if ( key.identifier === 'backspace' ) {
                if ( this.allSelected ) {
                    this.finalValue     =   0;
                    this.allSelected    =   false;
                } else {
                    this.finalValue     =   this.finalValue.toString();
                    this.finalValue     =   this.finalValue.substr(0, this.finalValue.length - 1 ) || 0;
                }
            } else {
                if ( this.allSelected ) {
                    this.finalValue     =   key.value;
                    this.finalValue     =   parseFloat( this.finalValue );
                    this.allSelected    =   false;
                } else {
                    this.finalValue     +=  '' + key.value;
                    this.finalValue     =   parseFloat( this.finalValue );

                    if ( this.mode === 'percentage' ) {
                        this.finalValue = this.finalValue > 100 ? 100 : this.finalValue;
                    }
                }
            } 
        }
    }
}
</script>