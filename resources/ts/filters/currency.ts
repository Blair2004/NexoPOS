import Vue from 'vue';
import NumeralJS from "numeral";

declare const ns;
declare const window;

const nsCurrency        =   Vue.filter( 'currency', ( value, format = 'full', locale = 'en' ) => {
    const precision     =   ( new Array( parseInt( ns.currency.ns_currency_precision ) ) ).fill('').map( _ => 0 ).join('');

    let numeralFormat, currencySymbol;

    switch( format ) {
        case 'abbreviate':
            numeralFormat = `0.00a`;
        break;
        default: 
            numeralFormat = `0${ns.currency.ns_currency_thousand_separator}0${ns.currency.ns_currency_decimal_separator}${precision}`;
        break;
    }

    NumeralJS.locale( locale );

    switch( ns.currency.ns_currency_prefered ) {
        case 'iso' :
            currencySymbol  =   ns.currency.ns_currency_iso;
        break;
        case 'symbol' :
            currencySymbol  =   ns.currency.ns_currency_symbol;
        break;
    }

    return `${ns.currency.ns_currency_position === 'before' ? currencySymbol : '' }${NumeralJS( value ).format( numeralFormat )}${ns.currency.ns_currency_position === 'after' ? currencySymbol : '' }`;
});

export { nsCurrency };