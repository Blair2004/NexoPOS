import Vue from 'vue';
import * as components from './components/components';

new Vue({
    el: '#dashboard-aside',
    components
});

new Vue({
    el: '#dashboard-content',
    components,
    mounted() {
        console.log( 'mounted' );
    }
});