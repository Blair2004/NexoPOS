<template>
    <div class="h-full w-full py-2">
        <div class="px-2 pb-2" v-if="order">
            <div class="grid grid-cols-2 gap-2">
                <div id="details" class="h-16 flex justify-between items-center border elevation-surface info text-xl md:text-3xl p-2">
                    <span>{{ __( 'Total' ) }} : </span>
                    <span>{{ nsCurrency( order.total ) }}</span>
                </div>
                <div id="discount" @click="toggleDiscount()" class="cursor-pointer h-16 flex justify-between items-center border elevation-surface error text-xl md:text-3xl p-2">
                    <span>{{ __( 'Discount' ) }} : </span>
                    <span>{{ nsCurrency( order.discount ) }}</span>
                </div>
                <div id="paid" class="h-16 flex justify-between items-center border elevation-surface success text-xl md:text-3xl p-2">
                    <span>{{ __( 'Paid' ) }} : </span>
                    <span>{{ nsCurrency( order.tendered ) }}</span>
                </div>
                <div id="change" class="h-16 flex justify-between items-center border elevation-surface warning text-xl md:text-3xl p-2">
                    <span>{{ __( 'Change' ) }} : </span>
                    <span>{{ nsCurrency( order.change ) }}</span>
                </div>
                <div id="change" class="col-span-2 h-16 flex justify-between items-center elevation-surface border text-xl md:text-3xl p-2">
                    <span>{{ __( 'Screen' ) }} : </span>
                    <span>{{ nsCurrency( backValue / number ) }}</span>
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
                            class="ns-numpad-key text-2xl border h-16 flex items-center justify-center cursor-pointer">
                            <span v-if="key.value !== undefined">{{ key.value }}</span>
                            <i v-if="key.icon" class="las" :class="key.icon"></i>
                        </div>
                        <div
                            @click="makeFullPayment()"
                            class="hover:bg-green-500 col-span-3 bg-success-secondary border-success-tertiary text-2xl text-white border h-16 flex items-center justify-center cursor-pointer">
                            {{ __( 'Full Payment' ) }}</div>
                    </div>
                </div>
                <div class="w-1/2 md:w-72 pr-2 pl-1">
                    <div class="grid grid-flow-row grid-rows-1 gap-2">
                        <div
                            v-for="(amount, index) of amountShortcuts" :key="index"
                            @click="increaseBy({ value : amount })"
                            class="ns-numpad-key text-2xl border h-16 flex items-center justify-center cursor-pointer">
                            <span>{{ nsCurrency( amount ) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { Popup } from '~/libraries/popup';
import nsPosDiscountPopupVue from '~/popups/ns-pos-discount-popup.vue';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import { __ } from '~/libraries/lang';
import { nsSnackBar } from '~/bootstrap';
import { nsCurrency } from '~/filters/currency';

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
            settings: {},
            settingsSubscription: null,
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
    computed: {
        amountShortcuts() {
            if ( nsShortcuts.ns_pos_amount_shortcut !== null ) {
                return nsShortcuts.ns_pos_amount_shortcut.split( '|' );
            }
            return [];
        }
    },
    mounted() {
        this.orderSubscription  =   POS.order.subscribe( order => {
            this.order  =   order;
        });

        this.settingsSubscription   =   POS.settings.subscribe( settings => {
            this.settings   =   settings;
        })

        /**
         * will bind keyboard event listening
         */
        const numbers   =   ( new Array(10) ).fill('').map( ( v,i ) => i );

        nsHotPress
            .create( 'numpad-keys' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( numbers, ( event, value ) => { this.inputValue({ value: value }); })

        nsHotPress
            .create( 'numpad-backspace' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( 'backspace', () => this.inputValue({ identifier: 'backspace' }))

        nsHotPress
            .create( 'numpad-save' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( 'enter', () => {
                /**
                 * if the actual amount on the screen is "0",
                 * and the key "enter" is pressed (we assume twice), we'll 
                 * emit the event "submit" to speed up the process.
                 */
                if ( this.backValue === '' ) {
                    this.$emit( 'submit' );
                    this.backValue  =   0;
                } else {
                    this.inputValue({ identifier: 'next' });
                }
            })
    },
    beforeDestroy() {
        nsHotPress.destroy( 'numpad-keys' );
        nsHotPress.destroy( 'numpad-backspace' );
        nsHotPress.destroy( 'numpad-save' );
    },
    unmounted() {
        this.orderSubscription.unsubscribe();
    },
    methods: {
        __,
        nsCurrency,
        toggleDiscount() {
            if ( this.settings.cart_discount !== undefined && this.settings.cart_discount === true ) {
                Popup.show( nsPosDiscountPopupVue, {
                    reference : this.order,
                    type : 'cart',
                    onSubmit : ( response ) => {
                        POS.updateCart( this.order, response );
                    }
                });
            } else {
                return nsSnackBar.error( __( `You're not allowed to add a discount on the cart.` ) ).subscribe();
            }
        },
        makeFullPayment() {
            const order     =   POS.order.getValue();
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Full Payment' ),
                message: __( 'A full payment will be made using {paymentType} for {total}' )
                    .replace( '{paymentType}', this.label )
                    .replace( '{total}', nsCurrency( this.order.total ) ),
                onAction: ( action ) => {
                    if ( action ) {
                        const order     =   POS.order.getValue();

                        /**
                         * to avoid inputing
                         * new amount when first payment fails.
                         */
                        if ( order.tendered < order.total ) {
                            POS.addPayment({
                                value: this.order.total - order.tendered,
                                identifier: this.identifier,
                                selected: false,
                                label: this.label,
                                readonly: false,
                            });
                        }

                        this.$emit( 'submit' );
                        this.backValue     =   '0';
                    }
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
            if ( key.identifier === 'next' ) {
                POS.addPayment({
                    value: parseFloat( this.backValue / this.number ),
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
                    this.backValue      =   this.backValue.slice(0, -1);
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

            if ( ( this.backValue ) === "0" ) {
                this.backValue      =   '';
            }
        }
    }
}
</script>