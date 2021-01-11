<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';


export default {
    name : 'ns-cash-flow',
    mounted() {
    },
    components: {
        nsDatepicker
    },
    data() {
        return {
            startDate: moment(),
            endDate: moment(),
            report: []
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
        loadReport() {
            const startDate     =   moment( this.startDate );
            const endDate       =   moment( this.endDate );

            nsHttpClient.post( '/api/nexopos/v4/reports/cash-flow', { startDate, endDate })
                .subscribe( result => {
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