<template>
    
</template>
<script>
import moment from "moment";
import nsDatepicker from "~/components/ns-datepicker.vue";
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { default as nsDateTimePicker } from '~/components/ns-date-time-picker.vue';

export default {
    name: 'ns-stock-report',
    data() {
        return {
            startDate: null,
            endDate: null,
            orders: []
        }
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
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
                    .map( order => order.taxes )
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

            nsHttpClient.post( '/api/reports/sale-report', { 
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