require('./bootstrap');

new window.Vue({
    el: '#dashboard-aside',
    mounded() {
    }
});

new window.Vue({
    el: '#dashboard-content',
    mounted() {
        console.log( 'mounted' );
    }
})