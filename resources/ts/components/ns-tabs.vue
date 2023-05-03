<template>
    <div class="tabs flex flex-col flex-auto ns-tab overflow-hidden" :selected-tab="activeComponent.identifier"
         ref="root">
        <div class="header ml-4 flex justify-between" style="margin-bottom: -1px;">
            <div class="flex flex-auto">
                <div
                    :key="tab.identifier"
                    v-for="( tab , identifier ) of children"
                    @click="toggle( tab )"
                    :class="active === tab.identifier ? 'border-b-0 active z-10' : 'border inactive'"
                    class="tab rounded-tl rounded-tr border  px-3 py-2 cursor-pointer"
                    style="margin-right: -1px">{{ tab.label }}</div>
            </div>
            <div>
                <slot name="extra"></slot>
            </div>
        </div>
        <slot></slot>
    </div>
</template>
<script lang="ts">
import { Subject } from 'rxjs';
import { __ } from '~/libraries/lang';
export default {
    data() {
        return {
            children: [],
            tabState: new Subject,
        }
    },
    props: ['active'],
    emits: ['active', 'changeTab'],
    computed: {
        activeComponent() {
            const active = this.children.filter(tab => tab.active);
            if (active.length > 0) {
                return active[0];
            }
            return false;
        },
    },
    beforeUnmount() {
        this.tabState.unsubscribe();
    },
    watch: {
        active( newValue, oldValue ) {
            this.children.forEach( child => {
                child.active     =   child.identifier === newValue;

                if ( child.active ) {
                    this.toggle( child );
                }
            });
        }
    },
    mounted() {
        this.buildChildren( this.active );
    },
    methods: {
        __,
        toggle( tab ) {
            this.$emit( 'active', tab.identifier );
            this.$emit( 'changeTab', tab.identifier );
            this.tabState.next( tab );
        },
        buildChildren(active) {
            this.children = Array.from(this.$refs.root.querySelectorAll('.ns-tab-item')).map((element: Element) => {
                const identifier = element.getAttribute('identifier') || undefined;

                let visible = true;

                if (element.getAttribute('visible')) {
                    visible = element.getAttribute('visible') === 'true';
                }

                return {
                    el: element,
                    active: active && active === identifier,
                    identifier,
                    initialized: false,
                    visible,
                    label: element.getAttribute('label') || __('Unnamed Tab')
                }
            }).filter(child => child.visible);

            /**
             * if no tabs is selected
             * we need at least to select the
             * first tab by default.
             */
            const hasActive = this.children.filter(element => element.active).length > 0;

            if (!hasActive && this.children.length > 0) {
                this.children[0].active = true;
            }

            this.children.forEach(child => {
                if (child.active) {
                    this.toggle(child);
                }
            });
        }
    },
}
</script>
