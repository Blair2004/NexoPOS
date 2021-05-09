import { nsHttpClient } from "@/bootstrap";

declare const nsLanguage;

export const __   =   function( text ) {
    console.log( nsLanguage.getEntries()[ text ] );
    return nsLanguage.getEntries()[ text ] || text;
}