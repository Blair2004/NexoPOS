<script>
    document.addEventListener( 'DOMContentLoaded', function() {
        nsEvent.subject().subscribe( event => {
            if ( event.identifier === 'ns-table-row-action' && event.value.action.identifier === 'change-delivery-status' ) {
                Popup.show( NsDriversOrdersPopup, {
                    order: event.value.row,
                    component: event.value.component
                });
            }

            if ( event.identifier === 'ns-table-row-action' && event.value.action.identifier === 'delivery-options' ) {
                Popup.show( NsDriversOrdersOptionsPopup, {
                    order: event.value.row,
                    component: event.value.component
                });
            }
        })
    });
</script>