/**
 * Must be used on component
 * that has the $popup object defined.
 */
export default function() {
    const keys  =   Object.keys( this );
    if ( keys.includes( '$popup' ) ) {
        this.$popup.event.subscribe( action => {
            if ( action.event === 'click-overlay' ) {
                this.$popup.close();
            }
    
            if ( action.event === 'press-esc' ) {
                this.$popup.close();
            }
        })
    }
}