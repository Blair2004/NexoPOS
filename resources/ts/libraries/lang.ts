import { nsHttpClient } from "~/bootstrap";

declare const nsLanguage;

export const __   =   function( text, namespace = 'NexoPOS' ) {
    return nsLanguage.getEntries( namespace ) ? (nsLanguage.getEntries( namespace )[ text ] || text ) : text;
}

export const __m   =   function( text, namespace ) {
    return nsLanguage.getEntries( namespace ) ? (nsLanguage.getEntries( namespace )[ text ] || text ) : text;
}