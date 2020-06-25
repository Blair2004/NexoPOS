
window.Vue.component( 'ns-button', {
    data: () => {
        return {
            clicked: false,
            _save: 0
        }
    },
    props: [ 'type', 'button', 'href' ],
    template: `
    <div>
        <button v-if="button" :class="buttonclass" class="rounded py-1 px-3 font-semibold"><slot></slot></button>
        <a v-if="!button" :href="href" :class="buttonclass" class="rounded py-1 px-3 font-semibold"><slot></slot></a>
    </div>
    `,
    mounted() {
    },
    computed: {
        buttonclass() {
            switch( type ) {
                case 'info':
                    return 'bg-blue-400 text-white'
                break;
                case 'success':
                    return 'bg-green-400 text-white'
                break;
                case 'danger':
                    return 'bg-red-400 text-white'
                break;
                case 'warning':
                    return 'bg-orange-400 text-white'
                break;
                default:
                    return 'bg-white text-gray-800'
                break;
            }
        }
    }
})