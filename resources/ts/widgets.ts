import { defineAsyncComponent } from 'vue';

window[ 'nsDashboardCards' ]              =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-dashboard-cards.vue' ) );
window[ 'nsBestCustomers' ]               =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-best-customers.vue' ) );
window[ 'nsBestCashiers' ]                =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-best-cashiers.vue' ) );
window[ 'nsOrdersSummary' ]               =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-orders-summary.vue' ) );
window[ 'nsOrdersChart' ]                 =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-orders-chart.vue' ) );
window[ 'nsCashierDashboard' ]            =   defineAsyncComponent( () => import( './pages/dashboard/home/ns-cashier-dashboard.vue' ) );