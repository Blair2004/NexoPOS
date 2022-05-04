<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { default as nsDateTimePicker } from '@/components/ns-date-time-picker';
import { __ } from '@/libraries/lang';
import FormValidation from '@/libraries/form-validation';
import nsSelectPopupVue from '@/popups/ns-select-popup.vue';

export default {
    name : 'ns-low-stock-report',
    mounted() {
        this.reportType     =   this.options[0].value;
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
    },
    data() {
        return {
            products: [],
            options: [{
                label: __( 'Low Stock Report' ),
                value: 'low_stock',
            }, {
                label: __( 'Stock Report' ),
                value: 'stock_report',
            }],
            reportType: '',
            reportTypeName: '',
            validation: new FormValidation,
        }
    },
    watch: {
        reportType() {
            const result    =   this.options.filter( option => option.value === this.reportType );

            if ( result.length > 0 ) {
                this.reportTypeName     =   result[0].label;
            } else {
                this.reportTypeName     =   __( 'N/A' );
            }

        }
    },
    methods: {
        __,
        async selectReport() {
            try {
                this.reportType     =   await new Promise( ( resolve, reject )  => {
                    Popup.show( nsSelectPopupVue, {
                        label: __( 'Report Type' ),
                        options: this.options,
                        resolve, reject
                    });
                })
            } catch( exception ) {
                // ...
            }
        },
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