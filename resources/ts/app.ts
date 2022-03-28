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

const NsRewardsSystem      =  () => import( './pages/dashboard/rewards-system.vue' );
const NsCreateCoupons      =  () => import( './pages/dashboard/create-coupons.vue' );
const NsSettings           =  () => import( './pages/dashboard/settings.vue' );
const NsReset              =  () => import( './pages/dashboard/reset.vue' );
const NsModules            =  () => import( './pages/dashboard/modules.vue' );
const NsPermissions        =  () => import( './pages/dashboard/ns-permissions.vue' );
const NsProcurement        =  () => import( './pages/dashboard/procurements/ns-procurement.vue' );
const NsManageProducts     =  () => import( './pages/dashboard/procurements/manage-products.vue' );
const NsProcurementInvoice =  () => import( './pages/dashboard/procurements/ns-procurement-invoice.vue' );
const NsNotifications      =  () => import( './pages/dashboard/ns-notifications.vue' );
const NsMedia              =  () => import( './pages/dashboard/ns-media.vue' );
const NsLowStockReport     =  () => import( './pages/dashboard/reports/ns-low-stock-report.vue' );
const NsSaleReport         =  () => import( './pages/dashboard/reports/ns-sale-report.vue' );
const NsSoldStockReport    =  () => import( './pages/dashboard/reports/ns-sold-stock-report.vue' );
const NsProfitReport       =  () => import( './pages/dashboard/reports/ns-profit-report.vue' );
const NsCashFlowReport     =  () => import( './pages/dashboard/reports/ns-cash-flow-report.vue' );
const NsYearlyReport       =  () => import( './pages/dashboard/reports/ns-yearly-report.vue' );
const NsBestProductsReport =  () => import( './pages/dashboard/reports/ns-best-products-report.vue' );
const NsPaymentTypesReport =  () => import( './pages/dashboard/reports/ns-payment-types-report.vue' );
const NsDashboardCards     =  () => import( './pages/dashboard/home/ns-dashboard-cards.vue' );
const NsBestCustomers      =  () => import( './pages/dashboard/home/ns-best-customers.vue' );
const NsBestCashiers       =  () => import( './pages/dashboard/home/ns-best-cashiers.vue' );
const NsOrdersSummary      =  () => import( './pages/dashboard/home/ns-orders-summary.vue' );
const NsOrdersChart        =  () => import( './pages/dashboard/home/ns-orders-chart.vue' );
const NsCashierDashboard   =  () => import( './pages/dashboard/home/ns-cashier-dashboard.vue' );
const NsStockAdjustment    =  () => import( './pages/dashboard/products/ns-stock-adjustment.vue' );
const NsOrderInvoice       =  () => import( './pages/dashboard/orders/ns-order-invoice.vue' );
const NsPromptPopup        =  () => import( './popups/ns-prompt-popup.vue' );
const NsAlertPopup         =  () => import( './popups/ns-alert-popup.vue' );
const NsConfirmPopup       =  () => import( './popups/ns-pos-confirm-popup.vue' );
const NsPOSLoadingPopup    =  () => import( './popups/ns-pos-loading-popup.vue' );

import RawVueApexCharts     from 'vue-apexcharts';
import VueHtmlToPaper       from 'vue-html-to-paper';

const nsState               =   window[ 'nsState' ];
const nsScreen              =   window[ 'nsScreen' ]; 
const nsExtraComponents     =   (<any>window)[ 'nsExtraComponents' ];    

const VueHtmlToPaperOptions     =   {
    name: '_blank',
    specs: [
      'fullscreen=yes',
      'titlebar=yes',
      'scrollbars=yes'
    ],
    styles: [
      '/css/app.css',
    ]
}

Vue.use( VueHtmlToPaper, VueHtmlToPaperOptions );
const VueApexCharts     =   Vue.component( 'vue-apex-charts', RawVueApexCharts );

const components    =   Object.assign({
    NsModules,
    NsRewardsSystem,
    NsCreateCoupons,
    NsManageProducts,
    NsSettings,
    NsReset,
    NsPermissions,
    NsProcurement,
    NsProcurementInvoice,
    NsMedia,
    NsDashboardCards,
    NsCashierDashboard,
    NsBestCustomers,
    NsBestCashiers,
    NsOrdersSummary,
    NsOrdersChart,
    NsNotifications,

    NsSaleReport,
    NsSoldStockReport,
    NsProfitReport,
    NsCashFlowReport,
    NsYearlyReport,
    NsPaymentTypesReport,
    NsBestProductsReport,
    NsLowStockReport,

    NsStockAdjustment,
    NsPromptPopup,
    NsAlertPopup,
    NsConfirmPopup,
    NsPOSLoadingPopup,
    NsOrderInvoice,
    VueApexCharts,
    ...baseComponents
}, nsExtraComponents );

const nsDashboardAside  =   new Vue({
    el: '#dashboard-aside',
    data: {
        sidebar: 'visible'
    },
    components,
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
    components,
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
    components,
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

(<any>window)[ 'nsComponents' ]          =   Object.assign( components, baseComponents );
(<any>window)[ 'nsDashboardContent' ]    =   new Vue({
    el: '#dashboard-content',
    components
});