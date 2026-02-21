<template>
    <slot></slot>
</template>
<script lang="ts">
import { nsHttpClient } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { nsCurrency, nsRawCurrency } from '~/filters/currency';

declare const nsComponents: any;

export default {
    name : 'ns-dashboard',
    data() {
        return {
            report: {}
        }
    },
    components: {
        ...nsComponents
    },
    mounted() {
        console.log( this );
    },
    methods: {
        __,
        nsCurrency,
        nsRawCurrency,
    }
}
</script>