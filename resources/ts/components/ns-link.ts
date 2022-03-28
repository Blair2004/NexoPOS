import Vue from 'vue';

const nsLink      =   Vue.component( 'ns-link', {
    data: () => {
        return {
            clicked: false,
            _save: 0
        }
    },
    props: [ 'type', 'to', 'href', 'target' ],
    template: `
    <div class="flex">
        <router-link v-if="to" :to="to" :class="buttonclass" class="rounded cursor-pointer py-2 px-3 font-semibold"><slot></slot></router-link>
        <a v-if="href" :target="target" :href="href" :class="buttonclass" class="rounded cursor-pointer py-2 px-3 font-semibold"><slot></slot></a>
    </div>
    `,
    computed: {
        buttonclass() {
            switch( this.type ) {
                case 'info':
                    return 'shadow bg-blue-400 text-white'
                break;
                case 'success':
                    return 'shadow bg-green-400 text-white'
                break;
                case 'error':
                    return 'shadow bg-red-400 text-white'
                break;
                case 'warning':
                    return 'shadow bg-orange-400 text-white'
                break;
                default:
                    return 'shadow bg-white text-gray-800'
                break;
            }
        }
    }
});

export { nsLink };