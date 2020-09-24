import Vue from 'vue';
export var nsCurrency = Vue.filter('currency', function (value) {
    var currency = new Intl.NumberFormat(ns.currency.ns_currency_position === 'before' ? 'en-US' : 'fr-FR', {
        currency: ns.currency.ns_currency_iso || 'USD',
        minimumFractionDigits: ns.currency.ns_currency_precision,
        style: 'currency',
    });
    if (parseFloat(value) >= 0) {
        return currency.formatToParts(value).map(function (_a) {
            var type = _a.type, value = _a.value;
            switch (type) {
                case 'decimal':
                    return (ns.currency.ns_currency_decimal_separator || '.');
                    break;
                case 'group':
                    return (ns.currency.ns_currency_thousand_separator || ',');
                    break;
                default: return value;
            }
        }).reduce(function (b, a) { return b + a; });
    }
    return currency.format(0);
});
//# sourceMappingURL=currency.js.map