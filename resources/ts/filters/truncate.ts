export const nsTruncate   =   (value, length) => {
    if ( !value ) {
        return '';
    } 
    
    value = value.toString();

    if( value.length > length ){
        return value.substring(0, length) + "..."
    } else {
        return value
    }
}