<script>
import moment from 'moment';
import { nsDatepicker } from '~/components/components';

export default {
    name: 'date',
    components: {
        nsDatepicker
    },
    computed: {
        formattedDate() {
            return moment( this.date ).format( 'YYYY-MM-DD HH:MM:ss' );
        }
    },
    data() {
        return {
            active: 'demo',
            date: moment().format(),
            field: {
                label: 'Date',
                name: 'range',
                description: 'a date range picker',
                value: {
                    startDate: moment().clone().subtract( 1, 'week' ).format(),
                    endDate: moment().format()
                }
            }
        }
    }
}
</script>
<template>
    <ns-page-title>
        <template #title>Date Picker</template>
        <template #description>A simple date picker</template>
    </ns-page-title>
    <ns-tabs :active="active" @active="active = $event">
        <ns-tabs-item identifier="general" label="General">
        </ns-tabs-item>
        <ns-tabs-item identifier="demo" label="Demo">
            <ns-datepicker @set="date = $event" :date="date"></ns-datepicker>
        </ns-tabs-item>
    </ns-tabs>
</template>