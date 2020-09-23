
/**
 * these are dynamic component
 * that are loaded conditionally
 */
window.NsPosDashboardButton         =   require( './pages/dashboard/pos/header-buttons/ns-pos-dashboard-button' ).default;
window.NsPosPendingOrderButton      =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'pending-orders' + '-button' ).default;
window.NsPosOrderTypeButton         =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'order-type' + '-button' ).default;
window.NsPosCustomersButton         =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'customers' + '-button' ).default;

/**
 * As POS object is defined on the
 * header, we can use that to reference the buttons (component)
 * that needs to be rendered dynamically
 */
POS.header.buttons.push( NsPosDashboardButton );
POS.header.buttons.push( NsPosPendingOrderButton );
POS.header.buttons.push( NsPosOrderTypeButton );
POS.header.buttons.push( NsPosCustomersButton );