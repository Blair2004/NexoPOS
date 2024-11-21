<template>
    <div id="prompt-popup" :class="size" class="rounded-lg overflow-hidden w-5/7-screen md:w-3/7-screen flex flex-col shadow-lg">
        <div class="flex items-center justify-center flex-col flex-auto p-2">
            <h2 class="text-xl md:text-2xl font-body text-center">{{ title }}</h2>
            <p class="py-4 text-sm md:text-base text-center">{{ message }}</p>
        </div>
        <div class="p-2">
            <div class="ns-input">
                <textarea v-if="type === 'textarea'" v-model="input" name="" id="" cols="30" rows="10" class="w-full border-2 p-2"></textarea>
                <input ref="input" @keypress.enter="emitAction( true )" v-if="type === 'input'" v-model="input" class="w-full border-2 p-2"/>
            </div>
        </div>
        <div class="flex border-t action-buttons">
            <button class="flex-auto w-1/2 h-16 flex items-center justify-center uppercase" @click="emitAction( true )">{{ __( 'Ok' ) }}</button>
            <hr class="border-r">
            <button class="flex-auto w-1/2 h-16 flex items-center justify-center uppercase" @click="reject( false )">{{ __( 'Cancel' ) }}</button>
        </div>
    </div>
</template>
<script lang="ts">
import { __ } from '~/libraries/lang';
export default {
    props: [ 'popup' ],
    data() {
        return {
            title: '',
            message: '',
            input: '',
            type: 'textarea'
        }
    },
    computed: {
        size() {
            return this.popup.params.size || 'h-full w-full'
        }
    },
    mounted() {
        this.input    =   this.popup.params.input || '';
        this.title    =   this.popup.params.title;
        this.message  =   this.popup.params.message;
        this.type     =   this.popup.params.type;
    },
    methods: {
        __,
        emitAction( action ) {
            this.popup.params.onAction( action ? this.input : action );
            this.popup.close();
        },
        reject( action ) {
            if( this.popup.params.reject !== undefined ) {
                this.popup.params.reject( action );
            }
            
            this.popup.close();
        }
    }
}
</script>