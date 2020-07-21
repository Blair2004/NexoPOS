const { Vue }           =   require('./bootstrap'); 
const { 
    nsButton,
    nsCheckbox,
    nsCrud,
    nsMenu,
    nsSubmenu 
}   =   require( './components/components' );

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
    }
});