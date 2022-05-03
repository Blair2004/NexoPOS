<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { default as nsDateTimePicker } from '@/components/ns-date-time-picker';
import { __ } from '@/libraries/lang';
import FormValidation from '@/libraries/form-validation';

export default {
    name : 'ns-low-stock-report',
    mounted() {
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
    },
    data() {
        return {
            products: [],
            validation: new FormValidation,
            fields: [{
                type: 'select',
                options: [{
                    label: __( 'Low stock' ),
                    value: 'low_stock',
                }, {
                    label: __( 'Stock Report' ),
                    value: 'all_stock',
                }],
                name: 'report_type',
                label: __( 'Choose The Report Type' ),
                value: 'low_stock',
            }]
        }
    },
    computed: {
        // ...
    },
    methods: {
        __,
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