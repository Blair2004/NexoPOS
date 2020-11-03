import Vue from 'vue';
import TwitterCldr from 'twitter_cldr';

declare const ns;
declare const window;

const nsCurrency    =   Vue.filter( 'currency', ( value, abbreviate, locale = 'en' ) => {
    const formater  =   TwitterCldr.load( locale );
    console.log( formater );
    const currency  =   new Intl.NumberFormat( ns.currency.ns_currency_position === 'before' ? 'en-US' : 'fr-FR', {
        currency: ns.currency.ns_currency_iso || 'USD',
        minimumFractionDigits: ns.currency.ns_currency_precision,
        style: 'currency',
        // @ts-ignore
        signDisplay: 'auto'
    });

    return currency.formatToParts( value ).map(({ type, value }) => {
         switch( type ) {
            case 'minusSign': return '-'; break;
            case 'decimal': return ( ns.currency.ns_currency_decimal_separator || '.' );break;
            case 'group': return ( ns.currency.ns_currency_thousand_separator || ',' ) ; break;
            default: return value;
        }
    }).join('');
});

export { nsCurrency };