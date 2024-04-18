<script type="module">
nsEvent.subject().subscribe( event => {
    if ( event.identifier === 'ns-table-row-action' && event.value.action.identifier === 'view-details' ) {
        Popup.show( nsAlertPopup, {
            title: __( 'Transaction Details' ),
            message: event.value.row.description || __( 'No description provided.' ),
        });
    }
});
</script>