<template>
    <div ref="dropZone" class="ns-drop-zone mb-4" @dragover.prevent>
        <slot></slot> <!-- To display anything inside the drop zone -->
    </div>
</template>

<script lang="ts">
import { ref } from 'vue';

export default {
    name: 'ns-dropzone',
    props: {
        dragged: {
            required: true,
        }
    },
    emits: ['dropped'],
    mounted() {
    },    
    setup(props, { emit }) {
        const dropZone = ref(null);

        const handleDrop = (event) => {
            console.log( dropZone.value );
            dropZone.value.style.border = '2px solid rgb(255 255 255 / 0%)';
            const id = event.dataTransfer.getData("text");
            emit('dropped', id);
        };

        return { dropZone, handleDrop };
    },
};
</script>
  
<style scoped>
    .ns-drop-zone {
        border: 2px solid rgb(255 255 255 / 0%);
    }
</style>