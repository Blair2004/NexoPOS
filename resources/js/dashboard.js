import Vue from 'vue';
var components = require('./components/components');
new Vue({
    el: '#dashboard-aside',
});
new Vue({
    el: '#dashboard-content',
    mounted: function () {
        console.log('mounted');
    }
});
//# sourceMappingURL=dashboard.js.map