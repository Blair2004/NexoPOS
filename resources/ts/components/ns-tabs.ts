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
            this.$emit( 'changeTab', tab.identifier );
        }
    },
    mounted() {
        this.childrens          =   this.$children;
    },
    template: `
    <div class="tabs flex flex-col flex-auto ns-tab">
        <div class="header flex" style="margin-bottom: -1px;">
            <div v-for="( tab , identifier ) of childrens" @click="toggle( tab )" :class="active === tab.identifier ? 'border-b-0 active z-10' : 'border inactive'" class="tab rounded-tl rounded-tr border  px-3 py-2 cursor-pointer" style="margin-right: -1px">{{ tab.label }}</div>
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
    props: [ 'label', 'identifier', 'padding' ],
    template: `
    <div :class="( padding || 'p-4' )" class="ns-tab-item border flex-auto" v-if="$parent.active === identifier">
        <slot></slot>
    </div>
    `
});

export { nsTabsItem, nsTabs };