<template>
    <sample-payment @submit="$emit( 'submit' )" identifier="bank-transfer" label="Bank Transfer"></sample-payment>
</template>
<script>
import { default as samplePayment  } from "./sample-payment";
export default {
    name: "bank-payment",
    components: {
        samplePayment
    }
}
</script>