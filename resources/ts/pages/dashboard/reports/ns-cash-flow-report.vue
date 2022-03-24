<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import { default as nsDateTimePicker } from '@/components/ns-date-time-picker';
import { nsHttpClient, nsSnackBar } from '@/bootstrap';


export default {
    name : 'ns-cash-flow',
    mounted() {
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
    },
    data() {
        return {
            startDate: moment(),
            endDate: moment(),
            report: new Object
        }
    },
    computed: {
        balance() {
            return Object.values( this.report ).length === 0 ? 0 : this.report.total_credit - this.report.total_debit;
        },
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
                .subscribe({
                    next: result => {
                        this.report     =   result;
                        console.log( this.report );
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