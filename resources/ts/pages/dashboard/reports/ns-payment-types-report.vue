<template>
    
</template>
<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { __ } from '@/libraries/lang';
import { default as nsDateTimePicker } from '@/components/ns-date-time-picker';

export default {
    name: 'ns-payment-types-report',
    data() {
        return {
            startDate: moment(),
            endDate: moment(),
            report: [],
            field: {
                type: 'datetimepicker',
                value: '2021-02-07',
                name: 'date'
            }
        }
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
    },
    computed: {
        
    },
    mounted() {

    },
    methods: {
        printSaleReport() {
            this.$htmlToPaper( 'sale-report' );
        },
        setStartDate( moment ) {
            console.log( moment );
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

            nsHttpClient.post( '/api/nexopos/v4/reports/payment-types', { 
                startDate: this.startDate,
                endDate: this.endDate
            }).subscribe( report => {
                this.report     =   report;
            }, ( error ) => {
                nsSnackBar.error( error.message ).subscribe();
            });
        },

        setEndDate( moment ) {
            console.log( moment );
            this.endDate    =   moment.format();
        },
    }
}
</script>