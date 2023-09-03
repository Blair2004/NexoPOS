<template>
    <div class="px-4">
        <ns-notice color="error" v-if="timezone === ''">
            <template v-slot:title>{{ __( 'An Error Has Occured' ) }}</template>
            <template v-slot:description>{{ __( 'Unable to load the report as the timezone is not set on the settings.' ) }}</template>
        </ns-notice>
        <div class="flex -mx-2" v-if="timezone !== ''">
            <div class="px-2">
                <input type="text" v-model="year" placeholder="{{ __( 'Year' ) }}" class="outline-none rounded border-gray-400 border-2 focus:border-blue-400 p-2">
            </div>
            <div class="px-2 flex">
                <button @click="loadReport()" class="rounded flex justify-between bg-white shadow py-1 items-center text-gray-700 px-2">
                    <i class="las la-sync-alt text-xl"></i>
                    <span class="pl-2">{{ __( 'Load' ) }}</span>
                </button>
            </div>
            <div class="px-2 flex">
                <button @click="printSaleReport()" class="rounded flex justify-between bg-white shadow py-1 items-center text-gray-700 px-2">
                    <i class="las la-print text-xl"></i>
                    <span class="pl-2">{{ __( 'Print' ) }}</span>
                </button>
            </div>
            <div class="px-2 flex">
                <button @click="recomputeForSpecificYear()" class="rounded flex justify-between bg-white shadow py-1 items-center text-gray-700 px-2">
                    <i class="las la-sync-alt text-xl"></i>
                    <span class="pl-2">{{ __( 'Recompute' ) }}</span>
                </button>
            </div>
        </div>
        <div id="annual-report" class="anim-duration-500 fade-in-entrance" v-if="timezone !== ''">
            <div class="flex w-full">
                <div class="my-4 flex justify-between w-full">
                    <div class="text-secondary">
                        <ul>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Date : {date}' ).replace( '{date}', ns.date.current ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'Document : Yearly Report' ) }}</li>
                            <li class="pb-1 border-b border-dashed">{{ __( 'By : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                    <div>
                        <img class="w-24" :src="storeLogo" :alt="storeName">
                    </div>
                </div>
            </div>
            <div class="bg-box-background shadow rounded my-4 overflow-hidden">
                <div class="border-b border-box-edge overflow-auto">
                    <table class="table ns-table w-full">
                        <thead class="">
                            <tr>
                                <th width="100" class="border p-2 text-left"></th>
                                <th width="150" class="border p-2 text-right">{{ __( 'Sales' ) }}</th>
                                <th width="150" class="border p-2 text-right">{{ __( 'Taxes' ) }}</th>
                                <th width="150" class="border p-2 text-right">{{ __( 'Expenses' ) }}</th>
                                <th width="150" class="border p-2 text-right">{{ __( 'Income' ) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border p-2 text-left">{{ __( 'January' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( report[1] ? report[1][ label ] : 0 ) ) }}</td>
                                </template>
                            </tr>
                            <tr>
                                <td class="border p-2 text-left">{{ __( 'Febuary' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( report[2] ? report[2][ label ] : 0 ) ) }}</td>
                                </template>
                            </tr>
                            <tr>
                                <td class="text-left border p-2">{{ __( 'March' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( report[3] ? report[3][ label ] : 0 ) ) }}</td>
                                </template>
                            </tr>
                            <tr>
                                <td class="text-left border p-2">{{ __( 'April' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( report[4] ? report[4][ label ] : 0 ) ) }}</td>
                                </template>
                            </tr>
                            <tr>
                                <td class="text-left border p-2">{{ __( 'May' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( report[5] ? report[5][ label ] : 0 ) ) }}</td>
                                </template>
                            </tr>
                            <tr>
                                <td class="text-left border p-2">{{ __( 'June' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( report[6] ? report[6][ label ] : 0 ) ) }}</td>
                                </template>
                            </tr>
                            <tr>
                                <td class="text-left border p-2">{{ __( 'July' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( report[7] ? report[7][ label ] : 0 ) ) }}</td>
                                </template>
                            </tr>
                            <tr>
                                <td class="text-left border p-2">{{ __( 'August' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( report[8] ? report[8][ label ] : 0 ) ) }}</td>
                                </template>
                            </tr>
                            <tr>
                                <td class="text-left border p-2">{{ __( 'September' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( report[9] ? report[9][ label ] : 0 ) ) }}</td>
                                </template>
                            </tr>
                            <tr>
                                <td class="text-left border p-2">{{ __( 'October' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( report[10] ? report[10][ label ] : 0 ) ) }}</td>
                                </template>
                            </tr>
                            <tr>
                                <td class="text-left border p-2">{{ __( 'November' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( report[11] ? report[11][ label ] : 0 ) ) }}</td>
                                </template>
                            </tr>
                            <tr>
                                <td class="text-left border p-2">{{ __( 'December' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( report[12] ? report[12][ label ] : 0 ) ) }}</td>
                                </template>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="text-left border p-2">{{ __( 'Total' ) }}</td>
                                <template :key="index" v-for="(label,index) of labels">
                                    <td class="border p-2 text-right">{{ nsCurrency( ( sumOf( label ) ) ) }}</td>
                                </template>
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
import nsNotice from "~/components/ns-notice.vue";
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import { default as nsDateTimePicker } from '~/components/ns-date-time-picker.vue';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';


export default {
    name : 'ns-yearly-report',
    props: [ 'storeLogo', 'storeName' ],
    mounted() {
        if ( this.timezone !== '' ) {
            this.year   =   ns.date.getMoment().format( 'Y' );
            this.loadReport();
        }
    },
    components: {
        nsDatepicker,
        nsNotice,
        nsDateTimePicker,
    },
    data() {
        return {
            startDate: moment(),
            endDate: moment(),
            report: {},
            timezone: ns.date.timeZone,
            year: '',
            ns: window.ns,
            labels: [ 'month_paid_orders', 'month_taxes', 'month_expenses', 'month_income' ]
        }
    },
    computed: {
        totalDebit() {
            return 0;
        },
        totalCredit() {
            return 0;
        }
    },
    methods: {
        __,
        nsCurrency,
        setStartDate( moment ) {
            this.startDate  =   moment.format();
        },
        setEndDate( moment ) {
            this.endDate    =   moment.format();
        },
        printSaleReport() {
            this.$htmlToPaper( 'annual-report' );
        },
        sumOf( label ) {
            if ( Object.values( this.report ).length > 0 ) {
                return Object.values( this.report ).map( month => parseFloat( month[ label ] ) || 0 )
                    .reduce( ( b, a ) => b + a );
            }

            return 0;
        },

        recomputeForSpecificYear() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Would you like to proceed ?' ),
                message: __( `The report will be computed for the current year, a job will be dispatched and you'll be informed once it's completed.` ),
                onAction: ( action ) => {
                    if ( action ) {
                        nsHttpClient.post( `/api/reports/compute/yearly`, {
                            year: this.year
                        }).subscribe( result => {
                            nsSnackBar.success( result.message ).subscribe();
                        }, ( error ) => {
                            nsSnackBar.success( error.message || __( 'An unexpected error has occurred.' ) ).subscribe();
                        })
                    }
                }
            });
        },

        getReportForMonth( month ) {
            return this.report[ month ];
        },

        loadReport() {
            const year       =   this.year;

            nsHttpClient.post( '/api/reports/annual-report', { year })
                .subscribe( result => {
                    this.report     =   result;
                }, ( error ) => {
                    nsSnackBar
                        .error( error.message )
                        .subscribe();
                })
        }
    }
}
</script>
