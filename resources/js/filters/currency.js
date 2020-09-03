const { Vue }       =   require( '../bootstrap' );

const nsCurrency    =   Vue.filter( 'currency', ( value ) => {
    
    const currency  =   new Intl.NumberFormat( ns.currency.ns_currency_position === 'before' ? 'en-US' : 'fr-FR', {
        currency: ns.currency.ns_currency_iso || 'USD',
        minimumFractionDigits: ns.currency.ns_currency_precision,
        style: 'currency',
    });

    if ( parseFloat( value ) >= 0 ) {
        return currency.formatToParts( value ).map(({ type, value }) => {
            switch( type ) {
                case 'decimal': return ( ns.currency.ns_currency_decimal_separator || '.' );break;
                case 'group': ( ns.currency.ns_currency_thousand_separator || ',' ) ; break;
                default: return value;
            }
        }).reduce( ( b, a ) => b + a );
    }

    return currency.format( 0 );
});

module.exports.nsCurrency   =   nsCurrency;