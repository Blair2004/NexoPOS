<script>
nsEvent.subject().subscribe( event => {
    if ( event.identifier === 'ns-table-row-action' && event.value.action.namespace === 'ns.quantities' ) {
        Popup.show( nsProductPreview, { product: event.value.row });
    }
});
</script>