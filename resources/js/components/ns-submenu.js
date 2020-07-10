const { Vue }   =   require( './../bootstrap' );

const nsSubmenu     =   Vue.component( 'ns-submenu', {
    data: () => {
        return {
        }
    },
    props: [ 'href', 'label' ],
    template: `
    <div>
        <li>
            <a :href="href" class="py-2 border-l-8 border-blue-800 px-3 block bg-gray-800 text-gray-100">
                <slot></slot>
            </a>
        </li>
    </div>
    `,
    mounted() {
    }
});

module.exports    =   nsSubmenu;