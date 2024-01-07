<template>
    <sample-payment :identifier="identifier" :label="label"></sample-payment>
</template>
<script>
import samplePayment from "./sample-payment.vue";
export default {
    name: 'cash-payment',
    props: [ 'identifier', 'label' ],
    components: {
        samplePayment
    }
}
</script>