/**
 * Will bootstrap time and 
 * start counting
 */
import './shared/time';

import * as baseComponents  from './components/components';

import {
    createApp,
    defineAsyncComponent
}  from 'vue/dist/vue.esm-bundler';

import { NsHotPress }       from './libraries/ns-hotpress';
import VueHtmlToPaper from './libraries/html-printer';

const nsRewardsSystem               =   defineAsyncComponent( () => import( '~/pages/dashboard/rewards-system.vue' ) );
const nsCreateCoupons               =   defineAsyncComponent( () => import( './pages/dashboard/create-coupons.vue' ) );
const nsSettings                    =   defineAsyncComponent( () => import( './pages/dashboard/ns-settings.vue' ) );
const nsReset                       =   defineAsyncComponent( () => import( './pages/dashboard/reset.vue' ) );
const nsModules                     =   defineAsyncComponent( () => import( './pages/dashboard/modules.vue' ) );
const nsPermissions                 =   defineAsyncComponent( () => import( './pages/dashboard/ns-permissions.vue' ) );
const nsProcurement                 =   defineAsyncComponent( () => import( './pages/dashboard/procurements/ns-procurement.vue' ) );
const nsManageProducts              =   defineAsyncComponent( () => import( './pages/dashboard/procurements/manage-products.vue' ) );
const nsProcurementInvoice          =   defineAsyncComponent( () => import( './pages/dashboard/procurements/ns-procurement-invoice.vue' ) );
const nsNotifications               =   defineAsyncComponent( () => import( './pages/dashboard/ns-notifications.vue' ) );
const nsMedia                       =   defineAsyncComponent( () => import( './pages/dashboard/ns-media.vue' ) );
const nsExpense                     =   defineAsyncComponent( () => import( './pages/dashboard/expenses/ns-expense.vue' ) );
const nsDashboard                   =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-dashboard.vue' ) );
const nsLowStockReport              =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-low-stock-report.vue' ) );
const nsSaleReport                  =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-sale-report.vue' ) );
const nsSoldStockReport             =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-sold-stock-report.vue' ) );
const nsProfitReport                =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-profit-report.vue' ) );
const nsCashFlowReport              =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-cash-flow-report.vue' ) );
const nsYearlyReport                =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-yearly-report.vue' ) );
const nsBestProductsReport          =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-best-products-report.vue' ) );
const nsPaymentTypesReport          =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-payment-types-report.vue' ) );
const nsCustomersStatementReport    =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-customers-statement-report.vue' ) );
const nsStockAdjustment             =   defineAsyncComponent( () => import( './pages/dashboard/products/ns-stock-adjustment.vue' ) );
const nsOrderInvoice                =   defineAsyncComponent( () => import( './pages/dashboard/orders/ns-order-invoice.vue' ) );

declare const window;
declare let nsExtraComponents;   

const nsState               =   window[ 'nsState' ];
const nsScreen              =   window[ 'nsScreen' ]; 

nsExtraComponents.nsToken       =   defineAsyncComponent( () => import( './pages/dashboard/profile/ns-token.vue' ) );

window.nsHotPress            =   new NsHotPress;

const allComponents    =   Object.assign({
    nsModules,
    nsRewardsSystem,
    nsCreateCoupons,
    nsManageProducts,
    nsSettings,
    nsReset,
    nsPermissions,
    nsProcurement,
    nsProcurementInvoice,
    nsMedia,
    nsExpense,
    nsDashboard,

    nsNotifications,
    nsSaleReport,
    nsSoldStockReport,
    nsProfitReport,
    nsCashFlowReport,
    nsYearlyReport,
    nsPaymentTypesReport,
    nsBestProductsReport,
    nsLowStockReport,
    nsCustomersStatementReport,

    nsStockAdjustment,
    nsOrderInvoice,
    ...baseComponents
}, nsExtraComponents );

window.nsDashboardAside     =   createApp({
    data() {
        return {
            sidebar: 'visible',
            popups: [],
        }
    },
    components: {
        nsMenu : baseComponents.nsMenu,
        nsSubmenu : baseComponents.nsSubmenu,
    },
    mounted() {
        nsState.subscribe(( state ) => {
            if ( state.sidebar ) {
                this.sidebar    =   state.sidebar;
            }
        });
    }
});

window.nsDashboardOverlay   =   createApp({
    data() {
        return {
            sidebar: null,
            popups: []
        }
    },
    components: allComponents,
    mounted() {
        nsState.subscribe( state => {
            if ( state.sidebar ) {
                this.sidebar    =   state.sidebar;
            }
        });
    },
    methods: {
        /**
         * this is mean to appear only on mobile.
         * If it's clicked, the menu should hide
         */
        closeMenu() {
            nsState.setState({
                sidebar: this.sidebar === 'hidden' ? 'visible' : 'hidden'
            });
        },
    }
});

window.nsDashboardHeader    =   createApp({
    data() {
        return {
            menuToggled: false,
            sidebar: null,
        }
    },
    components: allComponents,
    methods: {
        toggleMenu() {
            this.menuToggled    =   !this.menuToggled;
        },
        toggleSideMenu() {
            if ([ 'lg', 'xl' ].includes( nsScreen.breakpoint ) ) {
                nsState.setState({ sidebar: this.sidebar === 'hidden' ? 'visible': 'hidden' });    
            } else {
                nsState.setState({ sidebar: this.sidebar === 'hidden' ? 'visible': 'hidden' });
            }
        }
    },
    mounted() {
        nsState.subscribe( ( state ) => {
            if ( state.sidebar ) {
                this.sidebar    =   state.sidebar;
            }
        })
    }
});

window.nsDashboardContent   =   createApp({});

/**
 * let's register the component that has
 * a valid name globally
 */
for( let name in allComponents ) {
    window.nsDashboardContent.component( name, allComponents[ name ] );
}

/**
 * let's add the library
 * to the body dashboard content
 */
window.nsDashboardContent.use( VueHtmlToPaper, {
    styles: Object.values( window.ns.cssFiles )
});

window.nsComponents          =   Object.assign( allComponents, baseComponents );
window.nsDashboardAside.mount( '#dashboard-aside' );
window.nsDashboardOverlay.mount( '#dashboard-overlay' );
window.nsDashboardHeader.mount( '#dashboard-header' );
window.nsDashboardContent.mount( '#dashboard-content' );