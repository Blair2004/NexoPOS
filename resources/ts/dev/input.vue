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
    methods: {
        checkValidity() {
            this.validation.checkField( this.errorField );
        }
    },  
    data() {
        return {
            active: 'demo',
            validation: new FormValidation,
            date: moment().format(),
            field: {
                label: 'Input',
                name: 'range',
                type: 'text',
                validation: 'required',
                description: 'a date range picker',
                value: ''
            },
            errorField:  {
                label: 'Error Field',
                name: 'range',
                type: 'text',
                validation: 'required|min:6',
                description: 'a date range picker',
                value: ''
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
            <ns-field :field="field"/>
            <ns-field :field="errorField"/>
            <ns-button @click="checkValidity()">Check Validity</ns-button>
        </ns-tabs-item>
    </ns-tabs>
</template>