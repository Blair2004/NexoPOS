<template>
    <ns-date-time-picker :field="field"></ns-date-time-picker>
    {{ field }}
</template>
<script>
import nsDateTimePicker from '~/components/ns-date-time-picker.vue';
export default {
    components: {
        nsDateTimePicker
    },
    data() {
        return {
            field: {
                label: 'Date',
                name: 'date',
                description: 'a sample datetime field'
            }
        }
    }
}
</script>