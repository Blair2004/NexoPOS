declare const nsHotPress;

/**
 * Must be used on component
 * that has the popup object defined.
 */
export default function() {
    if ( this.popup !== undefined ) {
        /**
         * We'll listen to "esc" keypress
         * but proceed in certain conditions.
         */
        nsHotPress.create( 'popup-esc' )
            .whenPressed( 'escape', ( event ) => {
                event.preventDefault();

                /**
                 * We want to check if there is a popup that is
                 * displayed above the current one.
                 */
                const index             =   parseInt( this.$el.parentElement.getAttribute( 'data-index' ) );
                const possiblePopup     =   document.querySelector( `.is-popup [data-index="${index+1}]` );

                /**
                 * if the possible popup doesn't exists
                 * then we can close this one.
                 */
                if ( possiblePopup === null ) {
                    if ( this.popup.params && this.popup.params.reject !== undefined ) {
                        this.popup.params.reject( false );
                    }

                    this.popup.close();
                    nsHotPress.destroy( 'popup-esc' );
                }
            })
    }
}