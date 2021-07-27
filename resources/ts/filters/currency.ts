import Vue from 'vue';
import NumeralJS from "numeral";
import currency from 'currency.js';

declare const ns;
declare const window;

const precision     =   ( new Array( parseInt( ns.currency.ns_currency_precision ) ) ).fill('').map( _ => 0 ).join('');

const nsCurrency        =   Vue.filter( 'currency', ( value, format = 'full', locale = 'en' ) => {
    let numeralFormat, currencySymbol;

    switch( ns.currency.ns_currency_prefered ) {
        case 'iso' :
            currencySymbol  =   ns.currency.ns_currency_iso;
        break;
        case 'symbol' :
            currencySymbol  =   ns.currency.ns_currency_symbol;
        break;
    }

    const config            =   {
        decimal: ns.currency.ns_currency_decimal_separator,
        separator: ns.currency.ns_currency_thousand_separator,
        precision : parseInt( ns.currency.ns_currency_precision ),
        symbol: ''
    };

    const currencyValue     =   currency( value, config );

    return `${ns.currency.ns_currency_position === 'before' ? currencySymbol : '' }${ currencyValue.format() }${ns.currency.ns_currency_position === 'after' ? currencySymbol : '' }`;
});

const nsRawCurrency     =   ( value ) => {
    const numeralFormat = `0.${precision}`;
    return parseFloat( NumeralJS( value ).format( numeralFormat ) );
}

export { nsCurrency, nsRawCurrency };