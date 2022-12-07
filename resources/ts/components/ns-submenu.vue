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
                <a :class="active ? 'font-bold active' : 'normal'" :href="href" class="py-2 border-l-8 px-3 block ns-aside-submenu">
                    <slot></slot>
                </a>
            </template>
            <template v-else-if="to">
                <a :class="active ? 'font-bold active' : 'normal'" @click="goTo( to, $event )" :href="to" class="py-2 border-l-8 px-3 block ns-aside-submenu">
                    <slot></slot>
                </a>
            </template>
        </li>
    </div>
</template>