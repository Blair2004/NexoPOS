<template>
    <button class="outline-hidden ns-close-button hover:border-transparent border rounded-full h-6 min-w-[1.5rem] flex items-center justify-center">
        <i class="las la-times text-sm"></i> <slot></slot>
    </button>
</template>
<script>
export default {
    name: 'ns-close-button',
    methods: {
        // ...
    }
}
</script>