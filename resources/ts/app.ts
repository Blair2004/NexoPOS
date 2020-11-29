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

import NsRewardsSystem      from './pages/dashboard/rewards-system.vue';
import NsCreateCoupons      from './pages/dashboard/create-coupons.vue';
import NsSettings           from './pages/dashboard/settings.vue';
import NsReset              from './pages/dashboard/reset.vue';
import NsModules            from './pages/dashboard/modules.vue';
import NsPermissions        from './pages/dashboard/ns-permissions.vue';
import NsProcurement        from './pages/dashboard/procurements/ns-procurement.vue';
import NsManageProducts     from './pages/dashboard/procurements/manage-products.vue';
import NsProcurementInvoice from './pages/dashboard/procurements/ns-procurement-invoice.vue';
import NsNotifications      from './pages/dashboard/ns-notifications.vue';
import NsMedia              from './pages/dashboard/ns-media.vue';
import NsSaleReport         from './pages/dashboard/reports/ns-sale-report.vue';
import NsSoldStockReport    from './pages/dashboard/reports/ns-sold-stock-report.vue';
import NsProfitReport       from './pages/dashboard/reports/ns-profit-report.vue';
import NsDashboardCards     from './pages/dashboard/home/ns-dashboard-cards.vue';
import NsBestCustomers      from './pages/dashboard/home/ns-best-customers.vue';
import NsBestCashiers       from './pages/dashboard/home/ns-best-cashiers.vue';
import NsOrdersSummary      from './pages/dashboard/home/ns-orders-summary.vue';
import NsOrdersChart        from './pages/dashboard/home/ns-orders-chart.vue';
import NsStockAdjustment    from './pages/dashboard/products/ns-stock-adjustment.vue';
import NsOrderInvoice       from './pages/dashboard/orders/ns-order-invoice.vue';
import NsPromptPopup        from './popups/ns-prompt-popup.vue';
import NsAlertPopup         from './popups/ns-alert-popup.vue';
import NsConfirmPopup       from './popups/ns-pos-confirm-popup.vue';
import RawVueApexCharts     from 'vue-apexcharts';
import VueHtmlToPaper       from 'vue-html-to-paper';

declare const nsState;
declare const nsScreen;
declare const nsExtraComponents;

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

(<any>window).nsDashboardAside  =   new Vue({
    el: '#dashboard-aside',
    data: {
        sidebar: 'visible'
    },
    components: {...baseComponents},
    mounted() {
        nsState.behaviorState.subscribe(({ object }) => {
            this.sidebar    =   object.sidebar;
        })
    }
});

(<any>window).nsDashboardOverlay    =   new Vue({
    el: '#dashboard-overlay',
    data: {
        sidebar: null
    },
    components: {...baseComponents},
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

(<any>window).nsDashboardHeader     =   new Vue({
    el: '#dashboard-header',
    data: {
        menuToggled: false,
    },
    components: {
        ...baseComponents,
        NsNotifications,
    },
    methods: {
        toggleMenu() {
            this.menuToggled    =   !this.menuToggled;
        },
        toggleSideMenu() {
            if ([ 'lg', 'xl' ].includes( nsScreen.breakpoint ) ) {
                nsState.setState({ sidebar: this.sidebar === 'collapsed' ? 'visible': 'collapsed' });    
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

const components    =   {
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
    NsBestCustomers,
    NsBestCashiers,
    NsOrdersSummary,
    NsOrdersChart,
    NsSaleReport,
    NsSoldStockReport,
    NsProfitReport,
    NsStockAdjustment,
    NsPromptPopup,
    NsAlertPopup,
    NsConfirmPopup,
    NsOrderInvoice,
    VueApexCharts,
    ...nsExtraComponents, // add extra components provided by plugins.
};

(<any>window).nsComponents          =   { ...components, ...baseComponents };
(<any>window).nsDashboardContent    =   new Vue({
    el: '#dashboard-content',
    components
});