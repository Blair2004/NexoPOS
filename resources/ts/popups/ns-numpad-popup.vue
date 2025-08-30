<template>
    <div class="w-[85.71vw] md:w-[57.14vw] lg:w-[42.86vw] flex flex-col shadow-lg bg-popup-surface">
        <div class="flex flex-col">
            <div class="h-24 font-bold text-4xl text-fontcolor flex justify-center items-center">
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