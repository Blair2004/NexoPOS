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

new window.Vue({
    el: '#dashboard-content',
    mounted() {
        console.log( 'mounted' );
    }
});