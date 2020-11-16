/**
 * Must be used on component
 * that has the $popup object defined.
 */
export default function() {
    const keys  =   Object.keys( this );
    if ( keys.includes( '$popup' ) ) {
        this.$popup.event.subscribe( action => {
            if ( action.event === 'click-overlay' ) {
                if ( this.$popupParams && this.$popupParams.reject !== undefined ) {
                    this.$popupParams.reject( false );
                }

                this.$popup.close();
            }
    
            if ( action.event === 'press-esc' ) {
                if ( this.$popupParams && this.$popupParams.reject !== undefined ) {
                    this.$popupParams.reject( false );
                }

                this.$popup.close();
            }
        })
    }
}