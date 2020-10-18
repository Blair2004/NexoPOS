/**
 * Must be used on component
 * that has the $popup object defined.
 */
export default function() {
    this.$popup.event.subscribe( action => {
        if ( action.event === 'click-overlay' ) {
            this.$popup.close();
        }
    })
}