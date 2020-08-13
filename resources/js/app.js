const { Vue }           =   require('./bootstrap'); 
const { 
    nsButton,
    nsCheckbox,
    nsCrud,
    nsMenu,
    nsSubmenu
}   =   require( './components/components' );

const NsRewardsSystem   =   require( './pages/dashboard/rewards-system.vue' ).default;
const NsCreateCoupons   =   require( './pages/dashboard/create-coupons.vue' ).default;
const NsSettings        =   require( './pages/dashboard/settings.vue' ).default;
const NsReset           =   require( './pages/dashboard/reset.vue' ).default;

new window.Vue({
    el: '#dashboard-aside',
    mounded() {
        console.log( nsMenu );
    }
});

new window.Vue({
    el: '#dashboard-header',
    data: {
        menuToggled: false,
    },
    methods: {
        toggleMenu() {
            this.menuToggled    =   !this.menuToggled;
        }
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
        NsCreateCoupons,
        NsSettings,
        NsReset,
    }
});