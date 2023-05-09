declare const nsLanguage;

export const __   =   function( text, namespace = '' ) {
    return nsLanguage.getEntry( text, namespace) || text;
}

export const __m   =   function( text, namespace ) {
    return __(text, namespace);
}
