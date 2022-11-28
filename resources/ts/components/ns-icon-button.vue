<template>
    <button :class="type ? type : buttonClass " class="ns-inset-button rounded-full h-8 w-8 border items-center justify-center">
        <i :class="className" class="las"></i>
    </button>
</template>
<script>
export default {
    props: [ 'className', 'buttonClass', 'type' ],
}
</script>