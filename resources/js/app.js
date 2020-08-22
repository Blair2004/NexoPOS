const { Vue, nsState, nsScreen }           =   require('./bootstrap'); 
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
        sidebar: 'visible'
    },
    mounted() {
        nsState.behaviorState.subscribe(({ object }) => {
            this.sidebar    =   object.sidebar;
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

new window.Vue({
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

new window.Vue({
    el: '#dashboard-content',
    mounted() {

    },
    components: {
        NsRewardsSystem,
        NsCreateCoupons,
        NsManageProducts,
        NsSettings,
        NsReset,
    }
});