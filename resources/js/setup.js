import { Vue, VueRouter } from './bootstrap';
var WelcomeComponent = require('./pages/setup/welcome.vue').default;
var DatabaseComponent = require('./pages/setup/database.vue').default;
var SetupConfigurationComponent = require('./pages/setup/setup-configuration.vue').default;
var routes = [
    { path: '/', component: WelcomeComponent },
    { path: '/database', component: DatabaseComponent },
    { path: '/configuration', component: SetupConfigurationComponent },
];
var nsRouter = new VueRouter({ routes: routes });
new Vue({
    router: nsRouter
}).$mount('#nexopos-setup');
export { nsRouter };
//# sourceMappingURL=setup.js.map