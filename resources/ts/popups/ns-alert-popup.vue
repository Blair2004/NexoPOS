<template>
    <div id="alert-popup" :class="size" class="rounded-lg overflow-hidden w-[85.71vw] md:w-[57.14vw] lg:w-[42.86vw] flex flex-col shadow-lg">
        <div class="flex items-center justify-center flex-col flex-auto p-4">
            <h2 class="text-xl md:text-2xl font-body text-center" v-if="title">{{ title }}</h2>
            <p class="py-4 text-sm md:text-base text-center">{{ message }}</p>
        </div>
        <div class="action-buttons flex border-t justify-end items-center p-2">
            <ns-button @click="emitAction( true )" type="info">{{ __( 'Ok' ) }}</ns-button>
        </div>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang'
export default {
    data() {
        return {
            title: '',
            message: ''
        }
    },
    props: [ 'popup' ],
    computed: {
        size() {
            return this.popup.params.size || 'h-full w-full'
        }
    },
    mounted() {
        this.title          =   this.popup.params.title;
        this.message        =   this.popup.params.message;
    },
    methods: {
        __,
        emitAction( action ) {
            if ( this.popup.params.onAction !== undefined ) {
                this.popup.params.onAction( action );
            }
            this.popup.close();
        }
    }
}
</script>