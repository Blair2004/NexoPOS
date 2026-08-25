<template>
    <button :class="type ? type : buttonClass" class="ns-inset-button flex items-center justify-center rounded-full h-6 min-w-[1.5rem] border border-box-edge items-center justify-center">
        <i :class="className" class="las text-xs"></i>
    </button>
</template>
<script>
export default {
    props: [ 'className', 'buttonClass', 'type' ],
}
</script>