<template>
    <div id="report-section" class="px-4">
        <div class="flex -mx-2">
            <div class="px-2">
                <ns-field :field="startDateField"></ns-field>
            </div>
            <div class="px-2">
                <ns-field :field="endDateField"></ns-field>
            </div>
            <div class="px-2">
                <div class="ns-button">
                    <button @click="loadReport()" class="rounded flex justify-between text-primary shadow py-1 items-center  px-2">
                        <i class="las la-sync-alt text-xl"></i>
                        <span class="pl-2">{{ __( 'Load' ) }}</span>
                    </button>
                </div>
            </div>
            <div class="px-2">
                <div class="ns-button">
                    <button @click="printSaleReport()" class="rounded flex justify-between text-primary shadow py-1 items-center  px-2">
                        <i class="las la-print text-xl"></i>
                        <span class="pl-2">{{ __( 'Print' ) }}</span>
                    </button>
                </div>
            </div>
        </div>
        <div id="report" class="anim-duration-500 fade-in-entrance">
            <div class="flex w-full">
                <div class="my-4 flex justify-between w-full">
                    <div class="text-primary">
                        <ul>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Range : {date1} &mdash; {date2}' ).replace( '{date1}', startDateField.value ).replace( '{date2}', endDateField.value ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Document : Sale By Payment' ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'By : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                    <div>
                        <img class="w-24" :src="storeLogo" :alt="storeName">
                    </div>
                </div>
            </div>
            <div class="shadow rounded my-4">
                <div class="ns-box">
                    <div class="ns-box-body">
                        <table class="ns-table table w-full">
                            <thead class="">
                                <tr>
                                    <th class="border p-2 text-left">{{ __( 'Account' ) }}</th>
                                    <th width="150" class="border border-error-secondary bg-error-primary p-2 text-right">{{ __( 'Debit' ) }}</th>
                                    <th width="150" class="text-right border-success-secondary bg-success-primary border p-2">{{ __( 'Credit' ) }}</th>
                                </tr>
                            </thead>
                            <tbody class="">
                                <template v-for="( account, name ) in report.accounts" :key="name">
                                    <tr>
                                        <td class="p-2 bg-box-elevation-background border"><i class="las la-arrow-right"></i> <strong>{{ account.name }}</strong></td>
                                        <td class="p-2 border border-error-secondary bg-error-primary text-right">{{ nsCurrency( account.debits ) }}</td>
                                        <td class="p-2 border text-right border-success-secondary bg-success-primary">{{ nsCurrency( account.credits ) }}</td>
                                    </tr>
                                    <tr v-for="(transaction, key) of account.transactions" :key="key">
                                        <td class="p-2 border"><span class="ml-4">{{ transaction.name }}</span></td>
                                        <td class="p-2 border border-error-secondary bg-error-primary text-right">{{ nsCurrency( transaction.debits ) }}</td>
                                        <td class="p-2 border text-right border-success-secondary bg-success-primary">{{ nsCurrency( transaction.credits ) }}</td>
                                    </tr>
                                </template>
                            </tbody>
                            <tbody>
                                <tr>
                                    <td class="p-2 border bg-box-elevation-background">
                                        <strong>{{ __( 'Total' ) }}</strong>
                                    </td>
                                    <td class="p-2 border text-right border-error-secondary bg-error-primary">
                                        <strong>{{ nsCurrency( report.debits ) }}</strong>
                                    </td>
                                    <td class="p-2 border text-right border-success-secondary bg-success-primary">
                                        <strong>{{ nsCurrency( report.credits ) }}</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import moment from "moment";
import nsDatepicker from "~/components/ns-datepicker.vue";
import { default as nsDateTimePicker } from '~/components/ns-date-time-picker.vue';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';

export default {
    name : 'ns-cash-flow',
    props: [ 'storeLogo', 'storeName' ],
    mounted() {
        this.loadReport();
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
    },
    data() {
        return {
            startDateField: {
                value: moment( ns.date.current ).startOf( 'month' ).format( 'YYYY-MM-DD HH:mm:ss' ),
                type: 'datetimepicker'
            },
            endDateField: {
                value: moment( ns.date.current ).endOf( 'month' ).format( 'YYYY-MM-DD HH:mm:ss' ),
                type: 'datetimepicker'
            },
            report: new Object,
            ns: window.ns
        }
    },
    computed: {
        
    },
    methods: {
        __,
        nsCurrency,
        printSaleReport() {
            this.$htmlToPaper( 'report' );
        },
        loadReport() {
            nsHttpClient.post( '/api/reports/transactions', { startDate: this.startDateField.value, endDate: this.endDateField.value })
                .subscribe({
                    next: result => {
                        this.report     =   result;
                    },
                    error: ( error ) => {
                        nsSnackBar
                            .error( error.message )
                            .subscribe();
                    }
                })
        }
    }
}
</script>