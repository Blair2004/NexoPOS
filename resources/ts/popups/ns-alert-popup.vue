<template>
    <div id="alert-popup" :class="size" class="w-6/7-screen md:w-4/7-screen lg:w-3/7-screen flex flex-col shadow-lg">
        <div class="flex items-center justify-center flex-col flex-auto p-4">
            <h2 class="text-3xl font-body" v-if="title">{{ title }}</h2>
            <p class="py-4  text-center">{{ message }}</p>
        </div>
        <div class="action-buttons flex border-t justify-end items-center p-2">
            <ns-button @click="emitAction( true )" type="info">{{ __( 'Ok' ) }}</ns-button>
        </div>
    </div>
</template>
<script>
import { __ } from '@/libraries/lang'
export default {
    data() {
        return {
            title: '',
            message: ''
        }
    },
    computed: {
        size() {
            return this.$popupParams.size || 'h-full w-full'
        }
    },
    mounted() {
        this.title          =   this.$popupParams.title;
        this.message        =   this.$popupParams.message;
        this.$popup.event.subscribe( action => {
            if ( action.event === 'click-overlay' ) {
                if (this.$popupParams.onAction !== undefined ) {
                    this.$popupParams.onAction( false );
                }

                this.$popup.close();
            }
        })
    },
    methods: {
        __,
        emitAction( action ) {
            if ( this.$popupParams.onAction !== undefined ) {
                this.$popupParams.onAction( action );
            }
            this.$popup.close();
        }
    }
}
</script>