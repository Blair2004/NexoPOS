<template>
    <sample-payment @submit="$emit( 'submit' )" :identifier="identifier" label="Cash"></sample-payment>
</template>
<script>
import { default as samplePayment  } from "./sample-payment";
export default {
    name: 'cash-payment',
    props: [ 'identifier' ],
    components: {
        samplePayment
    }
}
</script>