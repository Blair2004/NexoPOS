<template>
    <div class="h-full w-full py-2">
        <div class="px-2 pb-2" v-if="order">
            <div class="grid grid-cols-2 gap-2">
                <div id="details" class="h-16 flex justify-between items-center elevation-surface border info text-xl md:text-3xl p-2">
                    <span>{{ __( 'Total' ) }} : </span>
                    <span>{{ nsCurrency( order.total ) }}</span>
                </div>
                <div id="discount" @click="toggleDiscount()" class="cursor-pointer h-16 flex justify-between items-center elevation-surface error border text-xl md:text-3xl p-2">
                    <span>{{ __( 'Discount' ) }} : </span>
                    <span>{{ nsCurrency( order.discount ) }}</span>
                </div>
                <div id="paid" class="h-16 flex justify-between items-center elevation-surface success border text-xl md:text-3xl p-2">
                    <span>{{ __( 'Paid' ) }} : </span>
                    <span>{{ nsCurrency( order.tendered ) }}</span>
                </div>
                <div id="change" class="h-16 flex justify-between items-center elevation-surface warning border text-xl md:text-3xl p-2">
                    <span>{{ __( 'Change' ) }} : </span>
                    <span>{{ nsCurrency( order.change ) }}</span>
                </div>
                <div id="change" class="col-span-2 h-16 flex justify-between items-center elevation-surface border success text-xl md:text-3xl p-2">
                    <span>{{ __( 'Current Balance' ) }} : </span>
                    <span>{{ nsCurrency( order.customer.account_amount ) }}</span>
                </div>
                <div id="change" class="col-span-2 h-16 flex justify-between items-center elevation-surface border text-primary text-xl md:text-3xl p-2">
                    <span>{{ __( 'Screen' ) }} : </span>
                    <span>{{ nsCurrency( screenValue ) }}</span>
                </div>
            </div>
        </div>
        <div class="px-2 pb-2">
            <div class="-mx-2 flex flex-wrap">
                <div class="pl-2 pr-1 flex-auto">
                    <ns-numpad :floating="true" @changed="handleChange( $event )" @next="proceedAddingPayment( $event )">
                        <template v-slot:numpad-footer>
                            <div
                            @click="makeFullPayment()"
                            class="hover:bg-success-tertiary col-span-3 bg-success-secondary text-2xl text-white border border-success-secondary h-16 flex items-center justify-center cursor-pointer">
                            {{ __( 'Full Payment' ) }}</div>
                        </template>
                    </ns-numpad>
                </div>
                <div class="w-1/2 md:w-72 pr-2 pl-1">
                    <div class="grid grid-flow-row grid-rows-1 gap-2">
                        <div 
                            @click="increaseBy({ value : 100 })"
                            class="elevation-surface border hoverable text-2xl text-primary h-16 flex items-center justify-center cursor-pointer">
                            <span>{{ nsCurrency( 100 ) }}</span>
                        </div>
                        <div 
                            @click="increaseBy({ value : 500 })"
                            class="elevation-surface border hoverable text-2xl text-primary h-16 flex items-center justify-center cursor-pointer">
                            <span >{{ nsCurrency( 500 ) }}</span>
                        </div>
                        <div 
                            @click="increaseBy({ value : 1000 })"
                            class="elevation-surface border hoverable text-2xl text-primary h-16 flex items-center justify-center cursor-pointer">
                            <span >{{ nsCurrency( 1000 ) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import nsNumpad from "~/components/ns-numpad.vue";
import { nsSnackBar } from '~/bootstrap';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';

export default {
    name: "ns-account-payment",
    components: {
        nsNumpad
    },
    props: [ 'identifier', 'label' ],
    data() {
        return {
            subscription: null,
            screenValue: 0,
            order: null,
        }
    },
    methods: {
        __,
        nsCurrency,
        handleChange( event ) {
            this.screenValue    =   event;
        },
        proceedAddingPayment( event ) {
            const value    =   parseFloat( event );
            const payments  =   this.order.payments;

            if ( value <= 0 ) {
                return nsSnackBar.error( __( 'Please provide a valid payment amount.' ) )
                    .subscribe();
            }

            if ( payments.filter( p => p.identifier === 'account-payment' ).length > 0 ) {
                return nsSnackBar.error( __( 'The customer account can only be used once per order. Consider deleting the previously used payment.' ) )
                    .subscribe();
            }

            if ( value > this.order.customer.account_amount ) {
                return nsSnackBar.error( __( 'Not enough funds to add {amount} as a payment. Available balance {balance}.' )
                    .replace( '{amount}', nsCurrency( value ) ) 
                    .replace( '{balance}', nsCurrency( this.order.customer.account_amount ) ) 
                ).subscribe();
            }

            POS.addPayment({
                value,
                identifier: 'account-payment',
                selected: false,
                label: this.label,
                readonly: false,
            });

            this.order.customer.account_amount  -=  value;
            
            POS.selectCustomer( this.order.customer );
        },
        proceedFullPayment() {
            const payments  =   this.order.payments;

            if ( payments.filter( p => p.identifier === 'account-payment' ).length > 0 ) {
                return nsSnackBar.error( __( 'The customer account can only be used once per order. Consider deleting the previously used payment.' ) )
                    .subscribe();
            }

            this.proceedAddingPayment( this.order.total );

            this.$emit( 'submit' );
        },
        makeFullPayment() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Full Payment' ),
                message: __( 'You\'re about to use {amount} from the customer account to make a payment. Would you like to proceed ?' ).replace( '{amount}', nsCurrency( this.order.total ) ),
                onAction: ( action ) => {
                    if ( action ) {
                        this.proceedFullPayment();
                    }
                }
            });
        },
    },
    mounted() {
        this.subscription   =   POS.order.subscribe( order => this.order = order );
    },
    unmounted() {
        this.subscription.unsubscribe();
    }
}
</script>