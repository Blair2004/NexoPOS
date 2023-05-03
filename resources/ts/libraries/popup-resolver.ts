/**
 * if either resolve and reject are defined
 * we need to resolve something when the popup
 * is being closed. Otherwise the popup is only closed
 * @param state
 * @param closeImmediately
 */
export default function( state: any, closeImmediately: boolean = false ) {
    if ( this.popup.params.resolve !== undefined && this.popup.params.reject ) {
        state !== false ? this.popup.params.resolve( state ) : this.popup.params.reject( state );
    }

    this.popup.close( null, closeImmediately );
}
