<script>
import moment from 'moment';
import { nsDaterangePicker } from '~/components/components';

export default {
    name: 'datepicker',
    components: {
        nsDaterangePicker
    },
    data() {
        return {
            active: 'demo',
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
        <template #title>Date Range Picker</template>
        <template #description>selects a range as a date.</template>
    </ns-page-title>
    <ns-tabs :active="active" @active="active = $event">
        <ns-tabs-item identifier="general" label="General">
            
        </ns-tabs-item>
        <ns-tabs-item identifier="demo" label="Demo">
            <ns-daterange-picker :field="field"></ns-daterange-picker>
        </ns-tabs-item>
    </ns-tabs>
</template>