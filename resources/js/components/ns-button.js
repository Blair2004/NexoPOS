const { Vue }       =   require( './../bootstrap' );
const nsButton      =   Vue.component( 'ns-button', {
    data: () => {
        return {
            clicked: false,
            _save: 0
        }
    },
    props: [ 'type', 'button', 'href', 'routerLink', 'to' ],
    template: `
    <div class="flex" @click="$emit( 'click' )">
        <button v-if="button" :class="buttonclass" class="rounded cursor-pointer py-2 px-3 font-semibold"><slot></slot></button>
        <router-link  :to="to" v-if="routerLink" :class="buttonclass" class="rounded cursor-pointer py-2 px-3 font-semibold"><slot></slot></router-link>
        <a v-if="!button" :href="href" :class="buttonclass" class="rounded cursor-pointer py-2 px-3 font-semibold"><slot></slot></a>
    </div>
    `,
    mounted() {
    },
    computed: {
        buttonclass() {
            switch( this.type ) {
                case 'info':
                    return 'shadow bg-blue-400 text-white'
                break;
                case 'success':
                    return 'shadow bg-green-400 text-white'
                break;
                case 'danger':
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

module.exports     =   nsButton;