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
                    <span>{{ backValue / number | currency }}</span>
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
                            @click="makeFullPayment()"
                            class="hover:bg-green-500 col-span-3 bg-green-400 text-2xl text-white border h-16 flex items-center justify-center cursor-pointer">
                            Full Payment</div>
                    </div>
                </div>
                <div class="w-1/2 md:w-72 pr-2 pl-1">
                    <div class="grid grid-flow-row grid-rows-1 gap-2">
                        <div 
                            @click="increaseBy({ value : 100 })"
                            class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-2xl text-gray-700 border h-16 flex items-center justify-center cursor-pointer">
                            <span>{{ 100 | currency }}</span>
                        </div>
                        <div 
                            @click="increaseBy({ value : 500 })"
                            class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-2xl text-gray-700 border h-16 flex items-center justify-center cursor-pointer">
                            <span >{{ 500 | currency }}</span>
                        </div>
                        <div 
                            @click="increaseBy({ value : 1000 })"
                            class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-2xl text-gray-700 border h-16 flex items-center justify-center cursor-pointer">
                            <span >{{ 1000 | currency }}</span>
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
import nsPosConfirmPopupVue from '../popups/ns-pos-confirm-popup.vue';
export default {
    name: 'sample-payment',
    props: [ 'label', 'identifier' ],
    data() {
        return {
            backValue: '0',
            number: parseInt( 
                1 + ( new Array( parseInt( ns.currency.ns_currency_precision ) ) )
                .fill('')
                .map( _ => 0 )
                .join('') 
            ),
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
        makeFullPayment() {
            Popup.show( nsPosConfirmPopupVue, {
                title: 'Confirm Full Payment',
                message: 'A full payment will be made using {paymentType} for {total}'
                    .replace( '{paymentType}', this.label )
                    .replace( '{total}', this.$options.filters.currency( this.order.total ) ),
                onAction: ( action ) => {
                    POS.addPayment({
                        amount: this.order.total,
                        identifier: this.identifier,
                        selected: false,
                        label: this.label,
                        readonly: false,
                    });
                    this.$emit( 'submit' );
                    this.backValue     =   '0';
                }
            })
        },
        increaseBy( key ) {
            let number    =   parseInt( 
                1 + ( new Array( this.cursor ) )
                .fill('')
                .map( _ => 0 )
                .join('') 
            );

            this.backValue      =   (( parseFloat( key.value ) * number ) + ( parseFloat( this.backValue ) || 0 ) ).toString();
            this.allSelected    =   false;
        },

        inputValue( key ) {
            let number    =   parseInt( 
                1 + ( new Array( this.cursor ) )
                .fill('')
                .map( _ => 0 )
                .join('') 
            );

            if ( key.identifier === 'next' ) {
                POS.addPayment({
                    amount: parseFloat( this.backValue / this.number ),
                    identifier: this.identifier,
                    selected: false,
                    label: this.label,
                    readonly: false,
                });
                this.backValue     =   '0';
            } else if ( key.identifier === 'backspace' ) {
                if ( this.allSelected ) {
                    this.backValue      =   '0';
                    this.allSelected    =   false;
                } else {
                    this.backValue      =   this.backValue.substr( 1 );
                }
            } else if ( key.value.toString().match( /^\d+$/ ) ) {
                if ( this.allSelected ) {
                    this.backValue      =   key.value.toString();
                    this.allSelected    =   false;
                } else {
                    this.backValue      +=  key.value.toString();

                    if ( this.mode === 'percentage' ) {
                        this.backValue = this.backValue > 100 ? 100 : this.backValue;
                    }
                }
            } 

            // this.finalValue     =   parseFloat( this.backValue ) / number || 0;

            // console.log( this.finalValue, this.backValue );

            if ( ( this.backValue ) === "0" ) {
                this.backValue      =   '';
            }
        }
    }
}
</script>