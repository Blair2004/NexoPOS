import Vue from 'vue';
import * as baseComponents from './components/components';


import {
    nsCurrency
}   from './filters/declarations';

/**
 * Will bootstrap time and 
 * start counting
 */
import './shared/time';

const nsRewardsSystem      =  () => import( './pages/dashboard/rewards-system.vue' );
const nsCreateCoupons      =  () => import( './pages/dashboard/create-coupons.vue' );
const nsSettings           =  () => import( './pages/dashboard/settings.vue' );
const nsReset              =  () => import( './pages/dashboard/reset.vue' );
const nsModules            =  () => import( './pages/dashboard/modules.vue' );
const nsPermissions        =  () => import( './pages/dashboard/ns-permissions.vue' );
const nsProcurement        =  () => import( './pages/dashboard/procurements/ns-procurement.vue' );
const nsManageProducts     =  () => import( './pages/dashboard/procurements/manage-products.vue' );
const nsProcurementInvoice =  () => import( './pages/dashboard/procurements/ns-procurement-invoice.vue' );
const nsNotifications      =  () => import( './pages/dashboard/ns-notifications.vue' );
const nsMedia              =  () => import( './pages/dashboard/ns-media.vue' );
const nsLowStockReport     =  () => import( './pages/dashboard/reports/ns-low-stock-report.vue' );
const nsSaleReport         =  () => import( './pages/dashboard/reports/ns-sale-report.vue' );
const nsSoldStockReport    =  () => import( './pages/dashboard/reports/ns-sold-stock-report.vue' );
const nsProfitReport       =  () => import( './pages/dashboard/reports/ns-profit-report.vue' );
const nsCashFlowReport     =  () => import( './pages/dashboard/reports/ns-cash-flow-report.vue' );
const nsYearlyReport       =  () => import( './pages/dashboard/reports/ns-yearly-report.vue' );
const nsBestProductsReport =  () => import( './pages/dashboard/reports/ns-best-products-report.vue' );
const nsPaymentTypesReport =  () => import( './pages/dashboard/reports/ns-payment-types-report.vue' );
const nsDashboardCards     =  () => import( './pages/dashboard/home/ns-dashboard-cards.vue' );
const nsBestCustomers      =  () => import( './pages/dashboard/home/ns-best-customers.vue' );
const nsBestCashiers       =  () => import( './pages/dashboard/home/ns-best-cashiers.vue' );
const nsOrdersSummary      =  () => import( './pages/dashboard/home/ns-orders-summary.vue' );
const nsOrdersChart        =  () => import( './pages/dashboard/home/ns-orders-chart.vue' );
const nsCashierDashboard   =  () => import( './pages/dashboard/home/ns-cashier-dashboard.vue' );
const nsStockAdjustment    =  () => import( './pages/dashboard/products/ns-stock-adjustment.vue' );
const nsOrderInvoice       =  () => import( './pages/dashboard/orders/ns-order-invoice.vue' );
const nsPromptPopup        =  () => import( './popups/ns-prompt-popup.vue' );
const nsAlertPopup         =  () => import( './popups/ns-alert-popup.vue' );
const nsConfirmPopup       =  () => import( './popups/ns-pos-confirm-popup.vue' );
const nsPOSLoadingPopup    =  () => import( './popups/ns-pos-loading-popup.vue' );

import RawVueApexCharts     from 'vue-apexcharts';
import VueHtmlToPaper       from 'vue-html-to-paper';
import { NsHotPress } from './libraries/ns-hotpress';

const nsState               =   window[ 'nsState' ];
const nsScreen              =   window[ 'nsScreen' ]; 
const nsCssFiles            =   (<any>window)[ 'ns' ].cssFiles;
const nsExtraComponents     =   (<any>window)[ 'nsExtraComponents' ];    

const VueHtmlToPaperOptions     =   {
    name: '_blank',
    specs: [
      'fullscreen=yes',
      'titlebar=yes',
      'scrollbars=yes'
    ],
    styles: [
      `/css/${nsCssFiles[ 'app.css' ]}`,
      `/css/${nsCssFiles[ 'light.css' ]}`,
      `/css/${nsCssFiles[ 'grid.css' ]}`,
      `/css/${nsCssFiles[ 'typography.css' ]}`,
    ]
};

( window as any ).nsHotPress            =   new NsHotPress;

Vue.use( VueHtmlToPaper, VueHtmlToPaperOptions );
const VueApexCharts     =   Vue.component( 'vue-apex-charts', RawVueApexCharts );

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

    nsPromptPopup,
    nsAlertPopup,
    nsConfirmPopup,
    nsPOSLoadingPopup,
    nsOrderInvoice,
    VueApexCharts,
    ...baseComponents
}, nsExtraComponents );

/**
 * let's register the component that has
 * a valid name globally
 */
for( let index in allComponents ) {
    if ( allComponents[ index ].name !== undefined && allComponents[ index ].name !== 'VueComponent' ) {
        Vue.component( allComponents[ index ].name, allComponents[ index ] );
    }   
}

const nsDashboardAside  =   new Vue({
    el: '#dashboard-aside',
    data: {
        sidebar: 'visible'
    },
    components: allComponents,
    mounted() {
        nsState.behaviorState.subscribe(({ object }:any) => {
            this.sidebar    =   object.sidebar;
        })
    }
});

(<any>window)[ 'nsDashboardAside' ]     =   nsDashboardAside;

(<any>window)[ 'nsDashboardOverlay' ]   =   new Vue({
    el: '#dashboard-overlay',
    data: {
        sidebar: null
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

(<any>window)[ 'nsDashboardHeader' ]     =   new Vue({
    el: '#dashboard-header',
    data: {
        menuToggled: false,
        sidebar: 'visible',
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

(<any>window)[ 'nsComponents' ]          =   Object.assign( allComponents, baseComponents );

(<any>window)[ 'nsDashboardContent' ]    =   new Vue({
    el: '#dashboard-content',
    components: allComponents,
});