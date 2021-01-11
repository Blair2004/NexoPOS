/**
 * if either resolve and reject are defined
 * we need to resolve something when the popup
 * is being closed. Otherwise the popup is only closed
 * @param state
 */
export default function( state: any ) {
    if ( this.$popupParams.resolve !== undefined && this.$popupParams.reject ) {
        state !== false ? this.$popupParams.resolve( state ) : this.$popupParams.reject( state );
    }
    this.$popup.close();
}