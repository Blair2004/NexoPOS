<template>
    <div id="confirm-popup" :class="size" class="rounded-lg overflow-hidden flex flex-col shadow-lg w-5/7-screen md:w-4/7-screen lg:w-2/7-screen">
        <div class="flex items-center justify-center flex-col flex-auto p-4">
            <h2 class="text-xl md:text-2xl text-center">{{ title }}</h2>
            <p class="py-4 text-sm md:text-base text-center">{{ message }}</p>
        </div>
        <div class="action-buttons flex justify-end border-t p-4">
            <button class="rounded font-bold px-2 py-1 bg-secondary h-10 flex items-center justify-center" @click="emitAction( true )">{{ __( 'Yes' ) }}</button>
            <button class="rounded font-bold cancel px-2 py-1 h-10 flex items-center justify-center" @click="emitAction( false )">{{ __( 'No' ) }}</button>
        </div>
    </div>
</template>
<script lqng="ts">
import { __ } from '~/libraries/lang';
import popupResolver from '~/libraries/popup-resolver';
import popupCloser from '~/libraries/popup-closer';

export default {
    props: [ 'popup' ],
    data() {
        return {
            title: '',
            message: ''
        }
    },
    computed: {
        size() {
            return this.popup.params.size || 'h-full w-full'
        }
    },
    mounted() {
        this.title          =   this.popup.params.title;
        this.message        =   this.popup.params.message;
        
        this.popupCloser();
    },
    methods: {
        __,
        popupResolver,
        popupCloser,
        
        emitAction( action ) {
            if ( typeof this.popup.params.onAction === 'function' ) {
                this.popup.params.onAction( action );
            }
            
            this.popup.close();
        }
    }
}
</script>