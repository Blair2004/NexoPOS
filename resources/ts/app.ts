import { 
    createApp, 
    defineAsyncComponent 
}  from 'vue/dist/vue.esm-bundler';

import NumeralJS            from "numeral";
import currency             from 'currency.js';
import * as baseComponents  from './components/components';
import { NsHotPress }       from './libraries/ns-hotpress';

/**
 * Will bootstrap time and 
 * start counting
 */
import './shared/time';
import { copyFileSync } from 'fs';

const nsRewardsSystem       =   defineAsyncComponent( () => import( '~/pages/dashboard/rewards-system.vue' ) );
const nsCreateCoupons       =   defineAsyncComponent( () => import( './pages/dashboard/create-coupons.vue' ) );
const nsSettings            =   defineAsyncComponent( () => import( './pages/dashboard/settings.vue' ) );
const nsReset               =   defineAsyncComponent( () => import( './pages/dashboard/reset.vue' ) );
const nsModules             =   defineAsyncComponent( () => import( './pages/dashboard/modules.vue' ) );
const nsPermissions         =   defineAsyncComponent( () => import( './pages/dashboard/ns-permissions.vue' ) );
const nsProcurement         =   defineAsyncComponent( () => import( './pages/dashboard/procurements/ns-procurement.vue' ) );
const nsManageProducts      =   defineAsyncComponent( () => import( './pages/dashboard/procurements/manage-products.vue' ) );
const nsProcurementInvoice  =   defineAsyncComponent( () => import( './pages/dashboard/procurements/ns-procurement-invoice.vue' ) );
const nsNotifications       =   defineAsyncComponent( () => import( './pages/dashboard/ns-notifications.vue' ) );
const nsMedia               =   defineAsyncComponent( () => import( './pages/dashboard/ns-media.vue' ) );
const nsLowStockReport      =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-low-stock-report.vue' ) );
const nsSaleReport          =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-sale-report.vue' ) );
const nsSoldStockReport     =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-sold-stock-report.vue' ) );
const nsProfitReport        =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-profit-report.vue' ) );
const nsCashFlowReport      =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-cash-flow-report.vue' ) );
const nsYearlyReport        =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-yearly-report.vue' ) );
const nsBestProductsReport  =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-best-products-report.vue' ) );
const nsPaymentTypesReport  =   defineAsyncComponent( () => import( './pages/dashboard/reports/ns-payment-types-report.vue' ) );
const nsDashboardCards      =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-dashboard-cards.vue' ) );
const nsBestCustomers       =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-best-customers.vue' ) );
const nsBestCashiers        =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-best-cashiers.vue' ) );
const nsOrdersSummary       =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-orders-summary.vue' ) );
const nsOrdersChart         =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-orders-chart.vue' ) );
const nsCashierDashboard    =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-cashier-dashboard.vue' ) );
const nsStockAdjustment     =   defineAsyncComponent( () => import( './pages/dashboard/products/ns-stock-adjustment.vue' ) );
const nsOrderInvoice        =   defineAsyncComponent( () => import( './pages/dashboard/orders/ns-order-invoice.vue' ) );

// import RawVueApexCharts     from 'vue-apexcharts';
// import VueHtmlToPaper       from 'vue-html-to-paper';

declare const window;

const nsState               =   window[ 'nsState' ];
const nsScreen              =   window[ 'nsScreen' ]; 
const nsCssFiles            =   (<any>window)[ 'ns' ].cssFiles;
const nsExtraComponents     =   (<any>window)[ 'nsExtraComponents' ];    

// const VueHtmlToPaperOptions     =   {
//     name: '_blank',
//     specs: [
//       'fullscreen=yes',
//       'titlebar=yes',
//       'scrollbars=yes'
//     ],
//     styles: [
//       `/css/${nsCssFiles[ 'app.css' ]}`,
//       `/css/${nsCssFiles[ 'light.css' ]}`,
//       `/css/${nsCssFiles[ 'grid.css' ]}`,
//       `/css/${nsCssFiles[ 'typography.css' ]}`,
//     ]
// };

window.nsHotPress            =   new NsHotPress;

// Vue.use( VueHtmlToPaper, VueHtmlToPaperOptions );
// const VueApexCharts     =   Vue.component( 'vue-apex-charts', RawVueApexCharts );

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
    nsDashboardCards,
    nsCashierDashboard,
    nsBestCustomers,
    nsBestCashiers,
    nsOrdersSummary,
    nsOrdersChart,
    nsNotifications,

    nsSaleReport,
    nsSoldStockReport,
    nsProfitReport,
    nsCashFlowReport,
    nsYearlyReport,
    nsPaymentTypesReport,
    nsBestProductsReport,
    nsLowStockReport,

    nsStockAdjustment,
    nsOrderInvoice,
    // VueApexCharts,
    ...baseComponents
}, nsExtraComponents );

const precision     =   ( new Array( parseInt( ns.currency.ns_currency_precision ) ) ).fill('').map( _ => 0 ).join('');

window.nsDashboardAside     =   createApp({
    data() {
        return {
            sidebar: 'visible',
        }
    },
    components: {
        nsMenu : baseComponents.nsMenu,
        nsSubmenu : baseComponents.nsSubmenu,
    },
    mounted() {
        nsState.behaviorState.subscribe(({ object }:any) => {
            this.sidebar    =   object.sidebar;
        });
    }
});

window.nsDashboardOverlay   =   createApp({
    data() {
        return {
            sidebar: null
        }
    },
    components: allComponents,
    mounted() {
        nsState.behaviorState.subscribe(({ object }) => {
            this.sidebar    =   object.sidebar;
        })
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
        }
    }
});

window.nsDashboardHeader    =   createApp({
    data() {
        return {
            menuToggled: false,
            sidebar: 'visible',
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
        nsState.behaviorState.subscribe( ({ object }) => {
            this.sidebar    =   object.sidebar;
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

window.nsComponents          =   Object.assign( allComponents, baseComponents );
window.nsDashboardAside.mount( '#dashboard-aside' );
window.nsDashboardOverlay.mount( '#dashboard-overlay' );
window.nsDashboardHeader.mount( '#dashboard-header' );
window.nsDashboardContent.mount( '#dashboard-content' );