<template>
    <sample-payment @submit="$emit( 'submit' )" :identifier="identifier" :label="label"></sample-payment>
</template>
<script>
import { default as samplePayment  } from "./sample-payment";
export default {
    name: "bank-payment",
    props: [ 'identifier', 'label' ],
    components: {
        samplePayment
    }
}
</script>