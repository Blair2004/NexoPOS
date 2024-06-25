<template>
    <div>
        <template v-if="to && ! hasChildren">
            <a @click="goTo( to, $event )" :href="to" :class="defaultToggledState ? 'toggled' : 'normal'" class="flex justify-between py-2 border-l-8 px-3 font-bold ns-aside-menu">
                <span class="flex items-center">
                <i class="las text-lg mr-2" :class="icon?.length > 0 ? icon : 'la-star'"></i>
                {{ label }}
                </span>
                <span v-if="notification > 0" class="rounded-full notification-label font-bold w-6 h-6 text-xs justify-center items-center flex">{{ notification }}</span>
            </a>
        </template>
        <template v-else>
            <a @click="toggleEmit()" :href="href || 'javascript:void(0)'" :class="defaultToggledState ? 'toggled' : 'normal'" class="flex justify-between py-2 border-l-8 px-3 font-bold  ns-aside-menu">
                <span class="flex items-center">
                <i class="las text-lg mr-2" :class="icon?.length > 0 ? icon : 'la-star'"></i>
                {{ label }}
                </span>
                <span v-if="notification > 0" class="rounded-full notification-label font-bold w-6 h-6 text-xs justify-center items-center flex">{{ notification }}</span>
            </a>
        </template>
        <ul :class="defaultToggledState ? '' : 'hidden'" class="submenu-wrapper">
            <slot></slot>                  
        </ul>
    </div>
</template>
<script lang="ts">
declare const nsEvent;

export default {
    data: () => {
        return {
            defaultToggledState : false,
            _save: 0,
            hasChildren: false
        }
    },
    props: [ 'href', 'to', 'label', 'icon', 'notification', 'toggled', 'identifier' ],
    mounted() {
        this.hasChildren    =   this.$el.querySelectorAll( '.submenu' ).length > 0;

        this.defaultToggledState   =   this.toggled !== undefined ? this.toggled : this.defaultToggledState;
        /**
         * subscribe to menu click
         * and check if the event is not emitted from
         * the current component. If yes, then skip
         */
        nsEvent.subject().subscribe( event => {
            if ( event.value !== this.identifier ) {
                this.defaultToggledState    =   false;
            }
        });
    },
    methods: {
        toggleEmit() {
            this.toggle().then( toggled => {
                if ( toggled ) {
                    nsEvent.emit({ 
                        identifier: 'side-menu.open',
                        value: this.identifier
                    });
                }
            })
        },
        goTo( url, event ) {
            this.$router.push( url );
            event.preventDefault();
            return false;
        },
        toggle() {                                                                                                                                                                                                                                                                                                                                                                                                  
            return new Promise( ( resolve, reject ) => {
                if ( !this.href || this.href.length === 0 ) {
                    this.defaultToggledState    =   !this.defaultToggledState;
                    resolve( this.defaultToggledState );
                }
            })
        }
    }
}
</script>