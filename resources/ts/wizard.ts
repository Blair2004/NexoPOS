import { createApp, defineAsyncComponent } from "vue";
import { nsButton } from "./components/components";
import NsAvatar from "./components/ns-avatar.vue";

const wizardApp     =   createApp({
    mounted() {
        console.log( 'Wizard App mounted' );
    }
});

wizardApp.component( 'ns-wizard', NsAvatar );

console.log({ wizardApp })

wizardApp.mount( '#wizard-wrapper' );