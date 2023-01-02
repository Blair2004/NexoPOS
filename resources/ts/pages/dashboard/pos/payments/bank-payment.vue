<template>
    <sample-payment :identifier="identifier" :label="label"></sample-payment>
</template>
<script>
import { default as samplePayment  } from "./sample-payment.vue";
export default {
    name: "bank-payment",
    props: [ 'identifier', 'label' ],
    components: {
        samplePayment
    }
}
</script>