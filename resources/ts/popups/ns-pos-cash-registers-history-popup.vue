<template>
    <div class="ns-box shadow-lg w-95vw md:w-4/6-screen lg:w-half overflow-hidden">
        <div id="header" class="p-2 flex justify-between items-center ns-box-header">
            <h3 class="font-bold">{{ __( 'Register History' ) }}</h3>
            <div>
                <ns-close-button @click="closePopup"></ns-close-button>
            </div>
        </div>
        <div class="flex w-full ns-box-body">
            <div class="flex flex-auto">
                <div class="w-full md:w-1/2 text-right bg-success-secondary text-white font-bold text-3xl p-3">{{ nsCurrency( totalIn ) }}</div>
                <div class="w-full md:w-1/2 text-right bg-error-secondary text-white font-bold text-3xl p-3">{{ nsCurrency( totalOut ) }}</div>
            </div>
        </div>
        <div class="flex flex-col overflow-y-auto h-72">
            <template v-for="history of cashRegisterReport.history">
                <div :key="history.id" v-if="[ 'register-order-payment' ].includes( history.action )"  class="flex border-b elevation-surface success">
                    <div class="p-2 flex-auto">{{ history.label }}</div>
                    <div class="flex-auto text-right p-2">{{ nsCurrency( history.value ) }}</div>
                </div>
                <div :key="history.id" v-if="[ 'register-order-change' ].includes( history.action )"  class="flex border-b elevation-surface warning">
                    <div class="p-2 flex-auto">{{ history.label }}</div>
                    <div class="flex-auto text-right p-2">{{ nsCurrency( history.value ) }}</div>
                </div>
                <div :key="history.id" v-if="[ 'register-cash-in' ].includes( history.action )"  class="flex border-b elevation-surface success">
                    <div class="p-2 flex-auto">
                        <div>{{ history.description || __( 'Not Provided' ) }}</div>
                        <div class="flex md:-mx-1">
                            <div class="px-1 text-xs text-secondary"><strong>{{ __( 'Type' ) }}</strong>: {{ history.label }}</div>
                        </div>
                    </div>
                    <div class="flex-auto text-right p-2">{{ nsCurrency( history.value ) }}</div>
                </div>
                <div :key="history.id" v-if="[ 'register-opening' ].includes( history.action )"  class="flex border-b elevation-surface">
                    <div class="p-2 flex-auto">{{ history.label }}</div>
                    <div class="flex-auto text-right p-2">{{ nsCurrency( history.value ) }}</div>
                </div>
                <div :key="history.id" v-if="[ 'register-close' ].includes( history.action )"  class="flex border-b elevation-surface info">
                    <div class="p-2 flex-auto">{{ history.label }}</div>
                    <div class="flex-auto text-right p-2">{{ nsCurrency( history.value ) }}</div>
                </div>
                <div :key="history.id" v-if="[ 'register-refund', 'register-cash-out' ].includes( history.action )"  class="flex border-b elevation-surface error">
                    <div class="p-2 flex-auto">
                        <div>{{ history.description || __( 'Not Provided' ) }}</div>
                        <div class="flex md:-mx-1">
                            <div class="px-1 text-xs text-secondary"><strong>{{ __( 'Type' ) }}</strong>: {{ history.label }}</div>
                            <div class="px-1 text-xs text-secondary"><strong>{{ __( 'Account' ) }}</strong>: {{ history.account_name }}</div>
                        </div>
                    </div>
                    <div class="flex-auto text-right p-2">{{ nsCurrency( history.value ) }}</div>
                </div>
            </template>
        </div>
        <div class="summary border-t border-box-edge">
            <div class="flex border-b elevation-surface" :class="summary.color" v-for="summary of cashRegisterReport.summary">
                <div class="p-2 flex-auto">{{ summary.label }}</div>
                <div class="flex-auto text-right p-2">{{ nsCurrency( summary.value ) }}</div>
            </div>
        </div>
        <div class="flex justify-between p-2">
            <div></div>
            <div>
                <ns-button @click="printZReport( )" type="info">{{ __( 'Print Z-Report' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>
<script>
import popupResolver from '~/libraries/popup-resolver'
import { nsHttpClient } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';

export default {
    props: [ 'popup' ],
    data() {
        return {
            totalIn: 0,
            totalOut: 0,
            settings: null,
            settingsSubscription: null,
            cashRegisterReport: [],
        }
    },
    mounted() {
        this.settingsSubscription   =   POS.settings.subscribe( settings => {
            this.settings   =   settings;
        });

        this.getHistory();
    },
    unmounted() {
        this.settingsSubscription.unsubscribe();
    },
    methods: {
        __,
        nsCurrency,
        popupResolver,

        closePopup() {
            this.popupResolver({
                status: 'success'
            });
        },

        printZReport() {
            POS.print.process( this.settings.register.id, 'z-report' );
        },

        getHistory() {
            nsHttpClient.get( `/api/cash-registers/session-history/${this.settings.register.id}` )
                .subscribe( cashRegisterReport  =>  {
                    this.cashRegisterReport      =   cashRegisterReport;
                    this.totalIn        =   this.cashRegisterReport.history
                        .filter( history => [ 'register-opening', 'register-order-payment', 'register-cash-in' ].includes( history.action ) )
                        .map( history => parseFloat( history.value ) )
                        .reduce( ( before, after ) => before + after, 0 );
                    this.totalOut        =   this.cashRegisterReport.history
                        .filter( history => [ 'register-order-change', 'register-closing', 'register-refund', 'register-cash-out' ].includes( history.action ) )
                        .map( history => parseFloat( history.value ) )
                        .reduce( ( before, after ) => before + after, 0 );
                });
        }
    }
}
</script>