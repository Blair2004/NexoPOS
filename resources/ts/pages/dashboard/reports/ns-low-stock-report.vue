<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { default as nsDateTimePicker } from '@/components/ns-date-time-picker';
import { __ } from '@/libraries/lang';
import FormValidation from '@/libraries/form-validation';
import nsSelectPopupVue from '@/popups/ns-select-popup.vue';
import nsPaginate from '@/components/ns-paginate.vue';

export default {
    name : 'ns-low-stock-report',
    mounted() {
        this.reportType     =   this.options[0].value;
        this.loadRelevantReport();
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
        nsPaginate,
    },
    data() {
        return {
            products: [],
            options: [{
                label: __( 'Stock Report' ),
                value: 'stock_report',
            },{
                label: __( 'Low Stock Report' ),
                value: 'low_stock',
            }],
            stockReportResult: {},
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
                const response     =   await new Promise( ( resolve, reject )  => {
                    Popup.show( nsSelectPopupVue, {
                        label: __( 'Report Type' ),
                        options: this.options,
                        resolve, reject
                    });
                });

                this.reportType     =   response[0].value;

                this.loadRelevantReport();
            } catch( exception ) {
                // ...
            }
        },
        loadRelevantReport() {
            switch( this.reportType ) {
                case 'stock_report':
                    this.loadStockReport();
                break;
                case 'low_stock':
                    this.loadReport();
                break;
            }
        },
        printSaleReport() {
            this.$htmlToPaper( 'low-stock-report' );
        },
        loadStockReport( url = null ) {
            nsHttpClient.get( url || '/api/nexopos/v4/reports/stock-report' )
                .subscribe({
                    next: result => {
                        this.stockReportResult   =   result;
                    }, 
                    error: ( error ) => {
                        nsSnackBar
                            .error( error.message )
                            .subscribe();
                    }
                })
        },
        totalSum( result, firstKey, secondKey ) {
            if ( result.data !== undefined ) {
                const unitQuantities    =   result.data.map( product => product.unit_quantities );

                const values            =   unitQuantities.map( unitQuantities => {
                    const result    =   unitQuantities.map( unitQuantity => unitQuantity[ firstKey ] * unitQuantity[ secondKey ] );
                    
                    if ( result.length > 0 ) {
                        return result.reduce( ( a, b ) => parseFloat( a ) + parseFloat( b ) );
                    }

                    return 0;
                });

                if ( values.length > 0 ) {
                    return values.reduce( ( a, b ) => parseFloat( a ) + parseFloat( b ) );
                }
            }

            return 0;
        },
        sum( result, type ) {
            if ( result.data !== undefined ) {
                const unitQuantities    =   result.data.map( product => product.unit_quantities );
                const values            =   unitQuantities.map( unitQuantities => {
                    const result    =   unitQuantities.map( unitQuantity => unitQuantity[ type ] );
                    
                    if ( result.length > 0 ) {
                        return result.reduce( ( a, b ) => parseFloat( a ) + parseFloat( b ) );
                    }

                    return 0;
                });

                if ( values.length > 0 ) {
                    return values.reduce( ( a, b ) => parseFloat( a ) + parseFloat( b ) );
                }
            }

            return 0;
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