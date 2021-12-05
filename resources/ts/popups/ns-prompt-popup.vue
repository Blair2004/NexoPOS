<template>
    <div id="popup" :class="size" class="w-5/7-screen md:w-3/7-screen flex flex-col bg-white shadow-lg">
        <div class="flex items-center justify-center flex-col flex-auto p-2">
            <h2 class="text-3xl font-body text-gray-700">{{ title }}</h2>
            <p class="w-full md:mx-auto md:w-2/3 py-4 text-gray-600 text-center">{{ message }}</p>
        </div>
        <div class="p-2">
            <textarea v-model="input" name="" id="" cols="30" rows="10" class="text-gray-700 w-full border-2 p-2 border-blue-400"></textarea>
        </div>
        <div class="flex border-t border-gray-200 text-gray-700">
            <button class="hover:bg-gray-100 flex-auto w-1/2 h-16 flex items-center justify-center uppercase" @click="emitAction( true )">{{ __( 'Ok' ) }}</button>
            <hr class="border-r border-gray-200">
            <button class="hover:bg-gray-100 flex-auto w-1/2 h-16 flex items-center justify-center uppercase" @click="reject( false )">{{ __( 'Cancel' ) }}</button>
        </div>
    </div>
</template>
<script>
import { __ } from '@/libraries/lang'
export default {
    data() {
        return {
            title: '',
            message: '',
            input: '',
        }
    },
    computed: {
        size() {
            return this.$popupParams.size || 'h-full w-full'
        }
    },
    mounted() {
        this.input          =   this.$popupParams.input || '';
        this.title          =   this.$popupParams.title;
        this.message        =   this.$popupParams.message;
        this.$popup.event.subscribe( action => {
            if ( action.event === 'click-overlay' ) {
                this.$popupParams.reject( false );
                this.$popup.close();
            }
        })
    },
    methods: {
        __,
        emitAction( action ) {
            this.$popupParams.onAction( action ? this.input : action );
            this.$popup.close();
        },
        reject( action ) {
            if( this.$popupParams.reject !== undefined ) {
                this.$popupParams.reject( action );
            }
            
            this.$popup.close();
        }
    }
}
</script>