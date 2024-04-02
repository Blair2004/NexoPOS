<template>
    <ns-page-title>
        <template #title>Date Time Picker</template>
        <template #description>A date time picker.</template>
    </ns-page-title>
    <ns-tabs :active="active" @active="active = $event">
        <ns-tabs-item identifier="general" label="General">
            
        </ns-tabs-item>
        <ns-tabs-item identifier="demo" label="Demo">
            <ns-date-time-picker :field="field"></ns-date-time-picker>
            <div>
                {{ field }}
            </div>
        </ns-tabs-item>
    </ns-tabs>
</template>
<script>
import nsDateTimePicker from '~/components/ns-date-time-picker.vue';
export default {
    components: {
        nsDateTimePicker
    },
    data() {
        return {
            active: 'demo',
            field: {
                label: 'Date',
                name: 'date',
                description: 'a sample datetime field',
                value: '',
            }
        }
    }
}
</script>