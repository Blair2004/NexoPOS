<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import { default as nsDateTimePicker } from '@/components/ns-date-time-picker';
import { nsHttpClient, nsSnackBar } from '@/bootstrap';


export default {
    name : 'ns-best-products-report',
    mounted() {
    },
    components: {
        nsDatepicker,
        nsDateTimePicker
    },
    data() {
        return {
            startDate: moment(),
            endDate: moment(),
            report: null,
            sort : '',
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
            this.$htmlToPaper( 'best-products-report' );
        },
        loadReport() {
            const startDate     =   moment( this.startDate );
            const endDate       =   moment( this.endDate );

            nsHttpClient.post( '/api/nexopos/v4/reports/products-report', { 
                    startDate : startDate.format( 'YYYY/MM/DD HH:mm' ), 
                    endDate : endDate.format( 'YYYY/MM/DD HH:mm' ),
                    sort: this.sort
                })
                .subscribe( result => {
                    result.current.products     =   Object.values( result.current.products );
                    this.report     =   result;
                    console.log( this.report );
                }, ( error ) => {
                    nsSnackBar
                        .error( error.message )
                        .subscribe();
                })
        }
    }
}
</script>