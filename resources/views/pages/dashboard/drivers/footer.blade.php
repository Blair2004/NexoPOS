<script>
    document.addEventListener( 'DOMContentLoaded', function() {
        nsEvent.subject().subscribe( event => {
            if ( event.identifier === 'ns-table-row-action' && event.value.action.identifier === 'change-status' ) {
                Popup.show( nsDriversStatusPopup, {
                    row: event.value.row,
                    component: event.value.component
                });
            }
        })
    })
</script>