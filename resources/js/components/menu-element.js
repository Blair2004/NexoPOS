
window.Vue.component( 'menu-element', {
    data: () => {
        return {
            clicked: false,
            _save: 0
        }
    },
    props: [ 'href', 'label', 'icon', 'notification', 'toggled', 'identifier' ],
    template: `
    <div>
        <a @click="toggleEmit()" :href="href || 'javascript:void(0)'" :class="clicked ? 'border-blue-800 bg-gray-800' : 'border-transparent bg-gray-900'" class="flex justify-between py-2 border-l-8 text-gray-200 px-3 font-bold  hover:bg-gray-700">
            <span class="flex items-center">
            <i class="las text-lg mr-2" :class="icon?.length > 0 ? icon : 'la-star'"></i>
            {{ label }}
            </span>
            <span v-if="notification > 0" class="rounded-full bg-red-600 text-white font-bold w-6 h-6 text-xs justify-center items-center flex">{{ notification }}</span>
        </a>
        <ul :class="clicked ? '' : 'hidden'" class="submenu-wrapper">
            <slot></slot>                  
        </ul>
    </div>
    `,
    mounted() {
        /**
         * subscribe to menu click
         * and check if the event is not emitted from
         * the current component. If yes, then skip
         */
        MenuEvent.subject().subscribe( event => {
            if ( event.value !== this.identifier ) {
                this.clicked    =   false;
            }
        })
    },
    methods: {
        toggleEmit() {
            this.toggle().then( clicked => {
                if ( clicked ) {
                    MenuEvent.emit({ 
                        identifier: 'side-menu.open',
                        value: this.identifier
                    });
                }
            })
        },
        toggle() {                                                                                                                                                                                                                                                                                                                                                                                                  
            return new Promise( ( resolve, reject ) => {
                if ( this.href.length === 0 ) {
                    this.clicked    =   !this.clicked;
                    resolve( this.clicked );
                }
            })
        }
    }
})