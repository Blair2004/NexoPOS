<script>
    document.addEventListener( 'DOMContentLoaded', function() {
        nsEvent.subject().subscribe( event => {
            if ( event.identifier === "ns-table-row-action" ) {
                if ( event.value.row.description !== null ) {
                    Popup.show( nsAlertPopup, { title: `{{ __( 'Description' ) }}`, message: event.value.row.description || __( 'No description was provided.' ) });
                } else {
                    nsSnackBar.error( 'The current operation doesn\'t have a description' ).subscribe();
                }
            }
        });
    })
</script>