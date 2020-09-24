declare const POS;

/**
 * these are dynamic component
 * that are loaded conditionally
 */
(<any>window).NsPosDashboardButton         =   require( './pages/dashboard/pos/header-buttons/ns-pos-dashboard-button' ).default;
(<any>window).NsPosPendingOrderButton      =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'pending-orders' + '-button' ).default;
(<any>window).NsPosOrderTypeButton         =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'order-type' + '-button' ).default;
(<any>window).NsPosCustomersButton         =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'customers' + '-button' ).default;

/**
 * As POS object is defined on the
 * header, we can use that to reference the buttons (component)
 * that needs to be rendered dynamically
 */
POS.header.buttons.push( (<any>window).NsPosDashboardButton );
POS.header.buttons.push( (<any>window).NsPosPendingOrderButton );
POS.header.buttons.push( (<any>window).NsPosOrderTypeButton );
POS.header.buttons.push( (<any>window).NsPosCustomersButton );

/**
 * this is resolved when a product is being added to the
 * cart. That will help to mutate the product before 
 * it's added the cart.
 */
// POS.settings.products_queue.push( promiseProductQuantity );
// POS.settings.products_queue.push( promiseProductUnit );
// POS.settings.products_queue.push( promiseProductConsolidation );
