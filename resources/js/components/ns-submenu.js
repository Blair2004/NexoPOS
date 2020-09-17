const { Vue }   =   require( './../bootstrap' );

const nsSubmenu     =   Vue.component( 'ns-submenu', {
    data: () => {
        return {
        }
    },
    props: [ 'href', 'label', 'active' ],
    mounted() {},
    template: `
    <div>
        <li>
            <a :class="active ? 'font-bold text-white' : 'text-gray-100'" :href="href" class="py-2 border-l-8 border-blue-800 px-3 block bg-gray-800 hover:bg-gray-700">
                <slot></slot>
            </a>
        </li>
    </div>
    `,
});

module.exports    =   nsSubmenu;