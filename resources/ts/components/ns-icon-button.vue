<template>
    <button :class="type ? type : buttonClass" class="ns-inset-button rounded-full h-6 min-w-[1.5rem] border border-input-edge items-center justify-center">
        <i :class="className" class="las"></i>
    </button>
</template>
<script>
export default {
    props: [ 'className', 'buttonClass', 'type' ],
}
</script>