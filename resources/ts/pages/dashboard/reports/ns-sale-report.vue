<template>
    
</template>
<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
// import nsDatetimepicker from "@/components/ns-datetimepicker";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';

export default {
    name: 'ns-sale-report',
    data() {
        return {
            startDate: moment(),
            endDate: moment(),
            orders: [],
            field: {
                type: 'datetimepicker',
                value: '2021-02-07',
                name: 'date'
            }
        }
    },
    components: {
        nsDatepicker,
        // nsDatetimepicker,
    },
    computed: {
        totalDiscounts() {
            if ( this.orders.length > 0 ) {
                return this.orders
                    .map( order => order.discount )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalTaxes() {
            if ( this.orders.length > 0 ) {
                return this.orders
                    .map( order => order.tax_value )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
        totalOrders() {
            if ( this.orders.length > 0 ) {
                return this.orders
                    .map( order => order.total )
                    .reduce( ( b, a ) => b + a );
            }
            return 0;
        },
    },
    methods: {
        printSaleReport() {
            this.$htmlToPaper( 'sale-report' );
        },
        setStartDate( moment ) {
            this.startDate  =   moment.format();
            console.log( this.startDate );
        },

        loadReport() {
            if ( this.startDate === null || this.endDate ===null ) {
                return nsSnackBar.error( 'Unable to proceed. Select a correct time range.' ).subscribe();
            }

            const startMoment   =   moment( this.startDate );
            const endMoment     =   moment( this.endDate );

            if ( endMoment.isBefore( startMoment ) ) {
                return nsSnackBar.error( 'Unable to proceed. The current time range is not valid.' ).subscribe();
            }

            nsHttpClient.post( '/api/nexopos/v4/reports/sale-report', { 
                startDate: this.startDate,
                endDate: this.endDate
            }).subscribe( orders => {
                this.orders     =   orders;
            }, ( error ) => {
                nsSnackBar.error( error.message ).subscribe();
            });
        },

        setEndDate( moment ) {
            this.endDate    =   moment.format();
        },
    }
}
</script>