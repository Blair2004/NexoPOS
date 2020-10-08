import Vue from "vue";

const nsTabs    =   Vue.component( 'ns-tabs', {
    data() {
        return {
            childrens: [],
        }
    },
    props: [ 'active' ],
    computed: {
        activeComponent() {
            const active    =   this.$children.filter( tab => tab.active );
            if ( active.length > 0 ) {
                return active[0];
            }
            return false;
        },
    },
    methods: {
        toggle( tab ) {
            this.$emit( 'active', tab.identifier );
        }
    },
    mounted() {
        this.childrens          =   this.$children;
    },
    template: `
    <div class="tabs flex flex-col flex-auto">
        <div class="header flex" style="margin-bottom: -1px;">
            <div v-for="( tab , identifier ) of childrens" @click="toggle( tab )" :class="active === tab.identifier ? 'border-b-0 bg-white z-10' : 'border bg-gray-200'" class="tab rounded-tl rounded-tr border border-gray-400  px-3 py-2 text-gray-700 cursor-pointer" style="margin-right: -1px">{{ tab.label }}</div>
        </div>
        <slot></slot>
    </div>
    `
})

const nsTabsItem    =   Vue.component( 'ns-tabs-item', {
    data() {
        return {}
    },
    mounted() {
    },
    props: [ 'label', 'identifier' ],
    template: `
    <div class="border flex-auto border-gray-400 p-4 bg-white" v-if="$parent.active === identifier">
        <slot></slot>
    </div>
    `
});

export { nsTabsItem, nsTabs };