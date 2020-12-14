<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';


export default {
    name : 'ns-yearly-report',
    mounted() {
        this.loadReport();
    },
    components: {
        nsDatepicker
    },
    data() {
        return {
            startDate: moment(),
            endDate: moment(),
            report: [],
            year: '2020',
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