<template>
    <h1>Hello World</h1>
</template>
<script>
export default {
    props: [],
    data() {

    },
    mounted() {

    },
    methods: {
        loadConfiguration() {
            nsHttpClient.get( `api/nexopos/v4/expenses/configurations` )
                .subscribe({
                    next: configuration => {
                        console.log( configuration );
                    }
                })
        }
    }

}
</script>