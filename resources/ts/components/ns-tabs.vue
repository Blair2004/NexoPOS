<template>
    <div class="tabs flex flex-col flex-auto ns-tab overflow-hidden" :selected-tab="activeComponent.identifier">
        <div class="header ml-4 flex justify-between" style="margin-bottom: -1px;">
            <div class="flex flex-auto">
                <div 
                    :key="tab.identifier" 
                    v-for="( tab , identifier ) of childrens" 
                    @click="toggle( tab )" 
                    :class="active === tab.identifier ? 'border-b-0 active z-10' : 'border inactive'" 
                    class="tab rounded-tl rounded-tr border px-2 py-1 cursor-pointer flex items-center" 
                    style="margin-right: -1px">
                        <span>{{ tab.label }}</span>
                        <div v-if="tab.closable" @click="$emit( 'close', tab )" class="ns-inset-button border border-box-edge text-xs hover:border-error-tertiary error rounded-full h-5 w-5 flex items-center justify-center ml-1"><i class="las la-times"></i></div>
                </div>
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
                    closable: element.getAttribute( 'closable' ) === 'true' ? true : false,
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