<template>
    <div class="w-6/7-screen md:w-4/7-screen lg:w-3/7-screen flex flex-col shadow-lg bg-popup-surface">
        <div class="flex flex-col">
            <div class="h-24 font-bold text-4xl text-primary flex justify-center items-center">
                {{ display }}
            </div>
            <ns-numpad-plus 
            @changed="updateQuantity( $event )" 
            @next="defineQuantity()" 
            :value="display"></ns-numpad-plus>
        </div>
    </div>
</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';

let display   =   ref('');
const props     =   defineProps([ 'popup' ]);

const updateQuantity    =   ( event ) => {
    display.value   =   event;
}

const defineQuantity    =   () => {
    props.popup.params.resolve( display.value );
    props.popup.close();
}

onMounted( () => {
    display.value   =   props.popup.params.value
})
</script>