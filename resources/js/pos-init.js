/**
 * these are dynamic component
 * that are loaded conditionally
 */
window.NsPosDashboardButton = require('./pages/dashboard/pos/header-buttons/ns-pos-dashboard-button').default;
window.NsPosPendingOrderButton = require('./pages/dashboard/pos/header-buttons/ns-pos-' + 'pending-orders' + '-button').default;
window.NsPosOrderTypeButton = require('./pages/dashboard/pos/header-buttons/ns-pos-' + 'order-type' + '-button').default;
window.NsPosCustomersButton = require('./pages/dashboard/pos/header-buttons/ns-pos-' + 'customers' + '-button').default;
/**
 * As POS object is defined on the
 * header, we can use that to reference the buttons (component)
 * that needs to be rendered dynamically
 */
POS.header.buttons.push(window.NsPosDashboardButton);
POS.header.buttons.push(window.NsPosPendingOrderButton);
POS.header.buttons.push(window.NsPosOrderTypeButton);
POS.header.buttons.push(window.NsPosCustomersButton);
/**
 * this is resolved when a product is being added to the
 * cart. That will help to mutate the product before
 * it's added the cart.
 */
// POS.settings.products_queue.push( promiseProductQuantity );
// POS.settings.products_queue.push( promiseProductUnit );
// POS.settings.products_queue.push( promiseProductConsolidation );
//# sourceMappingURL=pos-init.js.map