const { Vue }       =   require( '../bootstrap' );
const nsButton      =   Vue.component( 'ns-link', {
    data: () => {
        return {
            clicked: false,
            _save: 0
        }
    },
    props: [ 'type', 'to' ],
    template: `
    <div class="flex">
        <router-link :to="to" :class="buttonclass" class="rounded cursor-pointer py-2 px-3 font-semibold"><slot></slot></router-link>
    </div>
    `,
    mounted() {
        console.log( this );
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