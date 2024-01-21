<script>
    document.addEventListener( 'DOMContentLoaded', () => {
        nsHooks.addFilter( 'ns-before-saved', 'ns-custom-reset-settings', ( originalCallback ) => {
            return ( form ) => new Promise( ( resolve, reject ) => {
                Popup.show( nsConfirmPopup, {
                    title: __( 'Confirm Your Action' ),
                    message: __( 'The database will be cleared and all data erased. Only users and roles are kept. Would you like to proceed ?' ),
                    onAction: ( action ) => {
                        if ( action ) {
                            nsHttpClient.post( `/api/reset`, form.reset )
                                .subscribe({
                                    next: resolve,
                                    error: reject
                                })
                        } else {
                            reject( false );
                        }
                    }
                })
            });
        });
    })
</script>