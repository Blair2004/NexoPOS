const { Vue }           =   require('./bootstrap'); 
const { 
    nsButton,
    nsCheckbox,
    nsCrud,
    nsMenu,
    nsSubmenu
}   =   require( './components/components' );

const NsRewardsSystem    =   require( './pages/dashboard/rewards-system.vue' ).default;
const NsCreateCoupons    =   require( './pages/dashboard/create-coupons.vue' ).default;

new window.Vue({
    el: '#dashboard-aside',
    mounded() {
        console.log( nsMenu );
    }
});

new Vue({
    el: '#dashboard-header',
    data: {
        menuToggled: false,
    },
    mounted() {
        console.log( 'mounted' );
    }
});

new window.Vue({
    el: '#dashboard-content',
    mounted() {
        console.log( 'mounted' );
    },
    components: {
        NsRewardsSystem,
        NsCreateCoupons
    }
});