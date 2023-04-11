<template>
    <button class="outline-none ns-close-button hover:border-transparent border rounded-full h-8 min-w-[2rem] items-center justify-center">
        <i class="las la-times"></i> <slot></slot>
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