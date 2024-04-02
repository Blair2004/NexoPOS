<template>
    <div ref="dropZone" class="ns-drop-zone mb-4" @dragover.prevent>
        <slot></slot> <!-- To display anything inside the drop zone -->
    </div>
</template>

<script lang="ts">
import { ref } from 'vue';

export default {
    name: 'ns-dropzone',
    emits: ['dropped'],
    mounted() {
    },    
    setup(props, { emit }) {
        const dropZone = ref(null);

        const handleDrop = (event) => {
            const id = event.dataTransfer.getData("text");
            emit('dropped', id);
        };

        return { dropZone, handleDrop };
    },
};
</script>
  
<style scoped>
    .light .ns-drop-zone {
        @apply border-2 border-dashed border-slate-600
    }
    .dark .ns-drop-zone {
        @apply border-2 border-dashed border-slate-400
    }
</style>