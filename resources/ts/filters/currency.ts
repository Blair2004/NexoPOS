import NumeralJS from "numeral";
import Vue from 'vue';
import currency from 'currency.js';

declare const ns;
declare const window;

const precision     =   ( new Array( parseInt( ns.currency.ns_currency_precision ) ) ).fill('').map( _ => 0 ).join('');

const registerDynamicLocale = () => {
    const localeName = 'nexopos-dynamic';
    if (NumeralJS.locales[localeName]) return localeName;

    NumeralJS.register('locale', localeName, {
        delimiters: {
            thousands: ns.currency.ns_currency_thousand_separator,
            decimal: ns.currency.ns_currency_decimal_separator
        },
        abbreviations: {
            thousand: 'k',
            million: 'm',
            billion: 'b',
            trillion: 't'
        },
        currency: {
            symbol: ns.currency.ns_currency_symbol
        }
    });

    NumeralJS.locale(localeName);
    return localeName;
};

/**
 * Convert a number into a currency format.
 * @param value the value to convert
 * @param format amount format
 * @param locale locale
 * @returns string
 */
const nsCurrency    =   ( value, format = 'full', locale = 'en' ) => {
    registerDynamicLocale();

    let numeralFormat, currencySymbol;

    switch( ns.currency.ns_currency_prefered ) {
        case 'iso' :
            currencySymbol  =   ns.currency.ns_currency_iso;
        break;
        case 'symbol' :
            currencySymbol  =   ns.currency.ns_currency_symbol;
        break;
        default:
            currencySymbol = ns.currency.ns_currency_symbol || '$';
    }

    let newValue;

    if ( format === 'full' ) {
        const config            =   {
            decimal: ns.currency.ns_currency_decimal_separator,
            separator: ns.currency.ns_currency_thousand_separator,
            precision : parseInt( ns.currency.ns_currency_precision ),
            symbol: ''
        };
    
        newValue    =   currency( value, config ).format();
    } else {
        newValue = NumeralJS( value ).format( '0.0a' ).toUpperCase();
    }

    const isBefore = ns.currency.ns_currency_position === 'before';

    return isBefore
        ? `${currencySymbol} ${newValue}`.trim()
        : `${newValue} ${currencySymbol}`.trim();
}

const nsRawCurrency     =   ( value ) => {
    const numeralFormat = `0.000000000`;
    return parseFloat( NumeralJS( value ).format( numeralFormat ) );
}

/**
 * Will abbreviate an amount to return a short form.
 * @param value amount to abbreviate
 * @returns string
 */
const nsNumberAbbreviate    =   ( value ) => {
    return NumeralJS( value ).format( '0a' );
}

export { nsCurrency, nsRawCurrency, nsNumberAbbreviate };