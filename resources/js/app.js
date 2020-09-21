const { 
    nsButton,
    nsCheckbox,
    nsCrud,
    nsMenu,
    nsSubmenu,
    nsMediaInput,
}   =   require( './components/components' );

const {
    nsCurrency
}   =   require( './filters/declarations' );

/**
 * Will bootstrap time and 
 * start counting
 */
require( './shared/time' );

const NsRewardsSystem   =   require( './pages/dashboard/rewards-system.vue' ).default;
const NsCreateCoupons   =   require( './pages/dashboard/create-coupons.vue' ).default;
const NsManageProducts  =   require( './pages/dashboard/manage-products.vue' ).default;
const NsSettings        =   require( './pages/dashboard/settings.vue' ).default;
const NsReset           =   require( './pages/dashboard/reset.vue' ).default;
const NsModules         =   require( './pages/dashboard/modules.vue' ).default;
const NsPermissions     =   require( './pages/dashboard/ns-permissions.vue' ).default;
const NsProcurement     =   require( './pages/dashboard/ns-procurement.vue' ).default;
const NsMedia           =   require( './pages/dashboard/ns-media.vue' ).default;

window.nsDashboardAside  =   new window.Vue({
    el: '#dashboard-aside',
    data: {
        sidebar: 'visible'
    },
    mounted() {
        nsState.behaviorState.subscribe(({ object }) => {
            this.sidebar    =   object.sidebar;
        })
    }
});

window.nsDashboardOverlay    =   new window.Vue({
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
})

window.nsDashboardHeader     =   new window.Vue({
    el: '#dashboard-header',
    data: {
        menuToggled: false,
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
    NsMedia,
    ...nsExtraComponents, // add extra components provided by plugins.
};

window.nsDashboardContent    =   new window.Vue({
    el: '#dashboard-content',
    components
});