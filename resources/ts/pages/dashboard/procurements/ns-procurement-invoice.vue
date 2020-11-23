<script>
export default {
    template: '#ns-procurement-invoice',
    methods: {
        printInvoice() {
            this.$htmlToPaper( 'printable-container' )
        }
    }
}
</script>