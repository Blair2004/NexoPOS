const { Vue, VueRouter }   =   require( './bootstrap' );
const { 
    nsButton,
    nsCheckbox,
    nsCrud,
    nsMenu,
    nsSubmenu 
}   =   require( './components/components' );

const WelcomeComponent      =   require( './pages/setup/welcome.vue' ).default;
const DatabaseComponent     =   require( './pages/setup/database.vue' ).default;

const routes    =   [
    { path: '/', component: WelcomeComponent },
    { path: '/database', component: DatabaseComponent }
];

const router    =   new VueRouter({ routes });

new Vue({
    router
}).$mount( '#nexopos-setup' );