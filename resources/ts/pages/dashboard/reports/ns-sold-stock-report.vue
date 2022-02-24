<template>
    
</template>
<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { __ } from '@/libraries/lang';
import { default as nsDateTimePicker } from '@/components/ns-date-time-picker';

export default {
    name: 'ns-sold-stock-report',
    data() {
        return {
            startDate: moment(),
            endDate: moment(),
            products: []
        }
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
    },
    computed: {
        totalQuantity() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( product => product.quantity )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalTaxes() {
            if ( this.products.length > 0 ) {
                return this.products
                    .map( product => product.tax_value )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalPrice() {
            console.log( this.products );
            if ( this.products.length > 0 ) {
                return this.products
                    .map( product => product.total_price )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
    },
    methods: {
        printSaleReport() {
            this.$htmlToPaper( 'report-printable' );
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

            nsHttpClient.post( '/api/nexopos/v4/reports/sold-stock-report', { 
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
            this.endDate    =   moment.format();
        },
    }
}
</script>