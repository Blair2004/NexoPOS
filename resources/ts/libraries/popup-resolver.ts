/**
 * if either resolve and reject are defined
 * we need to resolve something when the popup
 * is being closed. Otherwise the popup is only closed
 * @param state
 */
export default function( state: any ) {
    if ( this.popup.params.resolve !== undefined && this.popup.params.reject ) {
        state !== false ? this.popup.params.resolve( state ) : this.popup.params.reject( state );
    }
    
    this.popup.close();
}