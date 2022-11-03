<script>
export default {
    data: () => {
        return {
        }
    },
    props: [ 'href', 'label', 'active', 'to' ],
    mounted() {
        // ...
    },
    methods: {
        goTo( url, event ) {
            this.$router.push( url );
            event.preventDefault();
            return false;
        },
    }
}
</script>
<template>
    <div>
        <li class="submenu">
            <template v-if="href">
                <a :class="active ? 'font-bold text-white dark:text-slate-300' : 'text-gray-100 dark:text-slate-200'" :href="href" class="py-2 border-l-8 border-blue-800 dark:border-slate-700 px-3 block bg-gray-800 dark:bg-slate-800 dark:hover:bg-slate-700 hover:bg-gray-700">
                    <slot></slot>
                </a>
            </template>
            <template v-else-if="to">
                <a :class="active ? 'font-bold text-white dark:text-slate-300' : 'text-gray-100 dark:text-slate-200'" @click="goTo( to, $event )" :href="to" class="py-2 border-l-8 border-blue-800 dark:border-slate-700 px-3 block bg-gray-800 dark:bg-slate-800 dark:hover:bg-slate-700 hover:bg-gray-700">
                    <slot></slot>
                </a>
            </template>
        </li>
    </div>
</template>