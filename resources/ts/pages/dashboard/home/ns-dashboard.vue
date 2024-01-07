<template>
    <slot></slot>
</template>
<script>
import { nsHttpClient } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { nsCurrency, nsRawCurrency } from '~/filters/currency';

export default {
    name : 'ns-dashboard',
    data() {
        return {
            report: {}
        }
    },
    mounted() {
    },
    methods: {
        __,
        nsCurrency,
        nsRawCurrency,
    }
}
</script>