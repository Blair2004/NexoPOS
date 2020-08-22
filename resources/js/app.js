const { Vue, nsState }           =   require('./bootstrap'); 
const { 
    nsButton,
    nsCheckbox,
    nsCrud,
    nsMenu,
    nsSubmenu
}   =   require( './components/components' );

const NsRewardsSystem   =   require( './pages/dashboard/rewards-system.vue' ).default;
const NsCreateCoupons   =   require( './pages/dashboard/create-coupons.vue' ).default;
const NsManageProducts  =   require( './pages/dashboard/manage-products.vue' ).default;
const NsSettings        =   require( './pages/dashboard/settings.vue' ).default;
const NsReset           =   require( './pages/dashboard/reset.vue' ).default;

new window.Vue({
    el: '#dashboard-aside',
    data: {
        sidebar: null
    },
    mounded() {
        nsState.behaviorState.subscribe(({ object }) => {
            this.sidebar    =   object.sidebar;
            console.log( object );
        })
    }
});

new window.Vue({
    el: '#dashboard-overlay',
    data: {
        sidebar: null
    },
    mounted() {
        nsState.behaviorState.subscribe(({ object }) => {
            this.sidebar    =   object.sidebar;
        })
    },
    methods: {
        closeMenu() {
            nsState.setState({
                sidebar: this.sidebar === 'hidden' ? 'visible' : 'hidden'
            });
        }
    }
})

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
        // we might need to detect the device side in order to trigger
        // the drawer
    },
    components: {
        NsRewardsSystem,
        NsCreateCoupons,
        NsManageProducts,
        NsSettings,
        NsReset,
    }
});