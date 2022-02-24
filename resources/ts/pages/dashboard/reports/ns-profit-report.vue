<template>
    
</template>
<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import nsDateTimePicker from "@/components/ns-date-time-picker";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { __ } from '@/libraries/lang';
export default {
    name: 'ns-profit-report',
    data() {
        return {
            startDate: moment(),
            endDate: moment(),
            products: []
        }
    },
    components: {
        nsDatepicker,
        nsDateTimePicker
    },
    computed: {
        totalQuantities() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( order => order.quantity )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalPurchasePrice() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( order => order.total_purchase_price )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalSalePrice() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( order => order.total_price )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalProfit() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( order => order.total_price - order.total_purchase_price )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalTax() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( order => order.tax_value )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
    },
    methods: {
        printSaleReport() {
            this.$htmlToPaper( 'profit-report' );
        },
        setStartDate( moment ) {
            this.startDate  =   moment.format();
        },

        loadReport() {
            if ( this.startDate === null || this.endDate ===null ) {
                return nsSnackBar.error( __( 'Unable to proceed. Select a correct time range.' ) ).subscribe();
            }

            const startMoment   =   moment( this.startDate );
            const endMoment     =   moment( this.endDate );

            if ( endMoment.isBefore( startMoment ) ) {
                return nsSnackBar.error( __( 'Unable to proceed. The current time range is not valid.' ) ).subscribe();
            }

            nsHttpClient.post( '/api/nexopos/v4/reports/profit-report', { 
                startDate: this.startDate,
                endDate: this.endDate
            }).subscribe({
                next: products => {
                    this.products     =   products;
                },
                error: ( error ) => {
                    nsSnackBar.error( error.message ).subscribe();
                }
            });
        },

        setEndDate( moment ) {
            console.log( moment );
            this.endDate    =   moment.format();
        },
    }
}
</script>