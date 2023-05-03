<template>
    <div id="report-section" class="px-4">
        <div class="flex -mx-2">
            <div class="px-2">
                <ns-date-time-picker :date="startDate" @change="setStartDate( $event )"></ns-date-time-picker>
            </div>
            <div class="px-2">
                <ns-date-time-picker :date="endDate" @change="setEndDate( $event )"></ns-date-time-picker>
            </div>
            <div class="px-2">
                <button @click="loadReport()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                    <i class="las la-sync-alt text-xl"></i>
                    <span class="pl-2">{{ __( 'Load' ) }}</span>
                </button>
            </div>
            <div class="px-2">
                <button @click="printSaleReport()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                    <i class="las la-print text-xl"></i>
                    <span class="pl-2">{{ __( 'Print' ) }}</span>
                </button>
            </div>
        </div>
        <div id="sale-report" class="anim-duration-500 fade-in-entrance">
            <div class="flex w-full">
                <div class="my-4 flex justify-between w-full">
                    <div class="text-secondary">
                        <ul>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Date : {date}' ).replace( '{date}', ns.date.current ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Document : Payment Type' ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'By : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                    <div>
                        <img class="w-24" :src="storeLogo" :alt="storeName">
                    </div>
                </div>
            </div>
            <div class="bg-box-background shadow rounded my-4">
                <div class="border-b border-box-edge">
                    <table class="table ns-table w-full">
                        <thead class="text-primary">
                            <tr>
                                <th class="text-primary border p-2 text-left">{{ __( 'Summary' ) }}</th>
                                <th width="150" class="text-primary border p-2 text-right">{{ __( 'Total' ) }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-primary">
                            <tr :key=index v-for="(summary,index) of report.summary" class="font-semibold">
                                <td class="p-2 border border-box-edge">{{ summary.label }}</td>
                                <td class="p-2 border text-right">{{ nsCurrency( summary.total ) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="text-primary font-semibold">
                            <tr>
                                <td class="p-2 border border-box-edge text-primary">{{ __( 'Total' ) }}</td>
                                <td class="p-2 border text-right">{{ nsCurrency( report.total ) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import moment from "moment";
import nsDatepicker from "~/components/ns-datepicker.vue";
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { default as nsDateTimePicker } from '~/components/ns-date-time-picker.vue';
import { nsCurrency } from '~/filters/currency';

export default {
    name: 'ns-payment-types-report',
    props: [ 'store-name', 'store-logo' ],
    data() {
        return {
            startDate: moment(),
            endDate: moment(),
            report: [],
            ns: window.ns,
            field: {
                type: 'datetimepicker',
                value: '2021-02-07',
                name: 'date'
            }
        }
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
    },
    computed: {
        
    },
    mounted() {

    },
    methods: {
        __,
        nsCurrency,
        printSaleReport() {
            this.$htmlToPaper( 'sale-report' );
        },
        setStartDate( moment ) {
            this.startDate  =   moment.format();
        },

        loadReport() {
            if ( this.startDate === null || this.endDate ===null ) {
                return nsSnackBar.error( __( 'Unable to proceed. Select a correct time range.' ) ).subscribe();
            }

            const startMoment   =   moment( this.startDate );
            const endMoment     =   moment( this.endDate );

            if ( endMoment.isBefore( startMoment ) ) {
                return nsSnackBar.error( __( 'Unable to proceed. The current time range is not valid.' ) ).subscribe();
            }

            nsHttpClient.post( '/api/reports/payment-types', { 
                startDate: this.startDate,
                endDate: this.endDate
            }).subscribe({
                next: report => {
                    this.report     =   report;
                },
                error: ( error ) => {
                    nsSnackBar.error( error.message ).subscribe();
                }
            });
        },

        setEndDate( moment ) {
            this.endDate    =   moment.format();
        },
    }
}
</script>