const { Vue }       =   require( './bootstrap' );
const components    =   require( './components/components' );

new Vue({
    el: '#dashboard-aside',
    mounded() {
    }
});

new Vue({
    el: '#dashboard-content',
    mounted() {
        console.log( 'mounted' );
    }
});