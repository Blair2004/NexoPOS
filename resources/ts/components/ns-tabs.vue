<template>
    <div class="tabs flex flex-col flex-auto ns-tab overflow-hidden" :selected-tab="activeComponent.identifier">
        <div class="header ml-4 flex justify-between" style="margin-bottom: -1px;">
            <div class="flex flex-auto">
                <div 
                    :key="tab.identifier" 
                    v-for="( tab , identifier ) of childrens" 
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
            childrens: [],
            tabState: new Subject,
        }
    },
    props: [ 'active' ],
    computed: {
        activeComponent() {
            const active    =   this.childrens.filter( tab => tab.active );
            if ( active.length > 0 ) {
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
            this.childrens.forEach( children => {
                children.active     =   children.identifier === newValue ? true : false;

                if ( children.active ) {
                    this.toggle( children );
                }
            });
        }
    },    
    mounted() {
        this.buildChildrens( this.active ); 
    },
    methods: {
        __,
        toggle( tab ) {
            this.$emit( 'active', tab.identifier );
            this.$emit( 'changeTab', tab.identifier );
            this.tabState.next( tab );
        },
        buildChildrens( active ) {
            this.childrens  =   Array.from( this.$el.querySelectorAll( '.ns-tab-item' ) ).map( element => {
                const identifier =  element.getAttribute( 'identifier' ) || undefined;
                
                let visible     =   true;

                if ( element.getAttribute( 'visible' ) ) {
                    visible     =   element.getAttribute( 'visible' ) === 'true' ? true : false;
                }

                return {
                    el: element,
                    active: active && active === identifier ? true : false,
                    identifier,
                    initialized: false,
                    visible,
                    label: element.getAttribute( 'label' ) || __( 'Unamed Tab' )
                }
            }).filter( child => child.visible );

            /**
             * if no tabs is selected
             * we need at least to select the 
             * first tab by default.
             */
            const hasActive     =   this.childrens.filter( element => element.active ).length > 0;

            if ( ! hasActive && this.childrens.length > 0 ) {
                this.childrens[0].active    =   true;
            }

            this.childrens.forEach( children => {
                if ( children.active ) {
                    this.toggle( children );
                }
            });
        }
    },
}
</script>