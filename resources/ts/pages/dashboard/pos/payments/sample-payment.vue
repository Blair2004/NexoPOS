<template>
    <div class="h-full w-full py-2">
        <div class="px-2 pb-2" v-if="order">
            <div class="grid grid-cols-2 gap-2">
                <div id="details" class="h-16 flex justify-between items-center bg-blue-400 text-white text-3xl p-2">
                    <span>Total : </span>
                    <span>{{ order.total | currency }}</span>
                </div>
                <div id="discount" @click="toggleDiscount()" class="cursor-pointer h-16 flex justify-between items-center bg-red-400 text-white text-3xl p-2">
                    <span>Discount : </span>
                    <span>{{ order.discount | currency }}</span>
                </div>
                <div id="paid" class="h-16 flex justify-between items-center bg-green-400 text-white text-3xl p-2">
                    <span>Paid : </span>
                    <span>{{ order.paid | currency }}</span>
                </div>
                <div id="change" class="h-16 flex justify-between items-center bg-teal-400 text-white text-3xl p-2">
                    <span>Change : </span>
                    <span>{{ order.change | currency }}</span>
                </div>
                <div id="change" class="col-span-2 h-16 flex justify-between items-center bg-gray-300 text-gray-800 text-3xl p-2">
                    <span>Screen : </span>
                    <span>{{ finalValue | currency }}</span>
                </div>
            </div>
        </div>
        <div class="px-2 pb-2">
            <div class="-mx-2 flex flex-wrap">
                <div class="pl-2 pr-1 flex-auto">
                    <div id="numpad" class="grid grid-flow-row grid-cols-3 gap-2 grid-rows-3" style="padding: 1px">
                        <div 
                            @click="inputValue( key )"
                            :key="index" 
                            v-for="(key,index) of keys" 
                            style="margin:-1px;"
                            class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-2xl text-gray-700 border h-16 flex items-center justify-center cursor-pointer">
                            <span v-if="key.value !== undefined">{{ key.value }}</span>
                            <i v-if="key.icon" class="las" :class="key.icon"></i>
                        </div>
                        <div
                            class="hover:bg-green-500 col-span-3 bg-green-400 text-2xl text-white border h-16 flex items-center justify-center cursor-pointer">
                            Full Payment</div>
                    </div>
                </div>
                <div class="w-1/2 md:w-72 pr-2 pl-1">
                    <div class="grid grid-flow-row grid-rows-1 gap-2">
                        <div 
                            class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-2xl text-gray-700 border h-16 flex items-center justify-center cursor-pointer">
                            <span @click="increaseBy({ value : 100 })">{{ 100 | currency }}</span>
                        </div>
                        <div 
                            class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-2xl text-gray-700 border h-16 flex items-center justify-center cursor-pointer">
                            <span @click="increaseBy({ value : 500 })">{{ 500 | currency }}</span>
                        </div>
                        <div 
                            class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-2xl text-gray-700 border h-16 flex items-center justify-center cursor-pointer">
                            <span @click="increaseBy({ value : 1000 })">{{ 1000 | currency }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { Popup } from '@/libraries/popup';
import nsPosDiscountPopupVue from '../popups/ns-pos-discount-popup.vue';
export default {
    name: 'sample-payment',
    props: [ 'label', 'identifier' ],
    data() {
        return {
            finalValue: 0,
            order: null,
            cursor: parseInt( ns.currency.ns_currency_precision ),
            orderSubscription: null,
            allSelected: true,
            keys: [
                ...([7,8,9].map( key => ({ identifier: key, value: key }))),
                ...([4,5,6].map( key => ({ identifier: key, value: key }))),
                ...([1,2,3].map( key => ({ identifier: key, value: key }))),
                ...[{ identifier: 'backspace', icon : 'la-backspace' },{ identifier: 0, value: 0 }, { identifier: 'next', icon: 'la-share' }],
            ]
        }
    },
    mounted() {
        this.orderSubscription  =   POS.order.subscribe( order => {
            this.order  =   order;
        });
    },
    destroyed() {
        this.orderSubscription.unsubscribe();
    },
    methods: {
        toggleDiscount() {
            Popup.show( nsPosDiscountPopupVue, { 
                reference : this.order,
                type : 'cart',
                onSubmit : ( response ) => {
                    POS.updateCart( this.order, response );
                }
            });
        },
        increaseBy( key ) {
            const total     =   parseFloat( key.value ) + parseFloat( this.finalValue );
            console.log( total );
            this.inputValue({ value: total });
        },

        inputValue( key ) {
            if ( key.identifier === 'next' ) {
                POS.addPayment({
                    amount: parseFloat( this.finalValue ),
                    identifier: this.identifier,
                    selected: false,
                    label: this.label,
                    readonly: false,
                });
                this.finalValue     =   0;
            } else if ( key.identifier === 'backspace' ) {

                if ( this.allSelected ) {
                    this.cursor         =   parseInt( ns.currency.ns_currency_precision );
                    this.finalValue     =   0;
                    this.allSelected    =   false;
                } else {
                    if( this.cursor < parseInt( ns.currency.ns_currency_precision ) ) {
                        this.cursor++;
                    }

                    if ( this.finalValue.toString().substr(0,1) === '.' || this.finalValue.toString().substr(0,2) === '0.' ) {
                        
                        if ( this.finalValue.toString().substr(0,2) === '0.' ) {
                            this.cursor++;
                        }
                        
                        // .21
                        let length          =   parseFloat( this.finalValue ).toFixed( ns.currency.ns_currency_precision ).length - 2; // 2
                        let number    =   parseInt( 
                            1 + ( new Array( length ) )
                            .fill('')
                            .map( _ => 0 )
                            .join('') 
                        ); // 100

                        this.finalValue     =   this.finalValue.toString().substr( this.cursor + 1 ) || 0; // 1 => 1
                        this.finalValue     =   (parseFloat( this.finalValue ) / number) || 0; // 1/100 = 0.01
                        this.finalValue     =   this.finalValue.toString().substr( 1, this.finalValue.length ) || 0; // .01
                    } else {
                        this.finalValue     =   this.finalValue.toString();
                        this.finalValue     =   this.finalValue.substr(1, this.finalValue.length ) || 0;
                    }
                    console.log( this.cursor, this.finalValue );
                }
            } else {
                let number;
                if ( this.cursor >= 0 ) {
                    number    =   parseInt( 
                        1 + ( new Array( this.cursor ) )
                        .fill('')
                        .map( _ => 0 )
                        .join('') 
                    );
                } else {
                    number     =    parseInt( 
                        1 + ( new Array( Math.abs( this.cursor ) ) )
                        .fill('')
                        .map( _ => 0 )
                        .join('') 
                    );
                }

                if ( this.allSelected ) {
                    this.finalValue     =   key.value;
                    this.finalValue     =   parseFloat( key.value ) === 0 ? 0 : this.cursor >= 0 ? parseFloat( this.finalValue ) / number : parseFloat( this.finalValue ) * number;
                    this.allSelected    =   false;
                } else {
                    this.finalValue     +=  parseFloat( key.value ) === 0 ? 0 : this.cursor >= 0 ? ( parseFloat( key.value ) / number ) : parseFloat( key.value ) * number;
                    this.finalValue     =   parseFloat( this.finalValue );

                    if ( this.mode === 'percentage' ) {
                        this.finalValue = this.finalValue > 100 ? 100 : this.finalValue;
                    }
                }

                this.cursor--;
                console.log( this.cursor );
            } 
        }
    }
}
</script>