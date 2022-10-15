<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import nsNotice from "@/components/ns-notice";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import nsPosConfirmPopupVue from '@/popups/ns-pos-confirm-popup.vue';
import { default as nsDateTimePicker } from '@/components/ns-date-time-picker';

export default {
    name : 'ns-yearly-report',
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
                        nsHttpClient.post( `/api/nexopos/v4/reports/compute/yearly`, {
                            year: this.year
                        }).subscribe( result => {
                            nsSnackBar.success( result.message ).subscribe();
                        }, ( error ) => {
                            nsSnackBar.success( error.message || __( 'An unexpected error has occured.' ) ).subscribe();
                        })
                    }
                }
            });
        },

        getReportForMonth( month ) {
            console.log( this.report, month );
            return this.report[ month ];
        },

        loadReport() {
            const year       =   this.year;

            nsHttpClient.post( '/api/nexopos/v4/reports/annual-report', { year })
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