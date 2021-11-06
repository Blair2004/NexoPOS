<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';


export default {
    name : 'ns-low-stock-report',
    mounted() {
    },
    components: {
        nsDatepicker
    },
    data() {
        return {
            products: [],
        }
    },
    computed: {
        // ...
    },
    methods: {
        printSaleReport() {
            this.$htmlToPaper( 'low-stock-report' );
        },
        loadReport() {
            nsHttpClient.get( '/api/nexopos/v4/reports/low-stock' )
                .subscribe({
                    next: result => {
                        this.products   =   result;
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