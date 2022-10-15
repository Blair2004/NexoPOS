import Vue from 'vue';

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
            <a :class="active ? 'font-bold text-white dark:text-slate-300' : 'text-gray-100 dark:text-slate-200'" :href="href" class="py-2 border-l-8 border-blue-800 dark:border-slate-700 px-3 block bg-gray-800 dark:bg-slate-800 dark:hover:bg-slate-700 hover:bg-gray-700">
                <slot></slot>
            </a>
        </li>
    </div>
    `,
});

export { nsSubmenu };