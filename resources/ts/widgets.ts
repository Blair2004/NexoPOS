import { defineAsyncComponent } from 'vue';

window[ 'nsBestCustomers' ]                 =   defineAsyncComponent( () => import( './widgets/ns-best-customers.vue' ) );
window[ 'nsBestCashiers' ]                  =   defineAsyncComponent( () => import( './widgets/ns-best-cashiers.vue' ) );
window[ 'nsOrdersSummary' ]                 =   defineAsyncComponent( () => import( './widgets/ns-orders-summary.vue' ) );
window[ 'nsOrdersChart' ]                   =   defineAsyncComponent( () => import( './widgets/ns-orders-chart.vue' ) );
window[ 'nsProfileWidget' ]                 =   defineAsyncComponent( () => import( './widgets/ns-profile-widget.vue' ) );
window[ 'nsSaleCardWidget' ]                =   defineAsyncComponent( () => import( './widgets/ns-sale-card-widget.vue' ) );
window[ 'nsIncompleteSaleCardWidget' ]      =   defineAsyncComponent( () => import( './widgets/ns-incomplete-sale-card-widget.vue' ) );
window[ 'nsExpenseCardWidget' ]             =   defineAsyncComponent( () => import( './widgets/ns-transaction-card-widget.vue' ) );