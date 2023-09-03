<template>
    <div class="flex ns-button" :class="type ? type : 'default'">
        <button :disabled="isDisabled" v-if="!link && !href" class="flex rounded items-center py-2 px-3 font-semibold"><slot></slot></button>
        <a v-if="href" :target="target" :href="href" class="flex rounded items-center py-2 px-3 font-semibold"><slot></slot></a>
    </div>
</template>
<script>
export default {
    data: () => {
        return {
            clicked: false,
            _save: 0
        }
    },
    props: [ 'type', 'disabled', 'link', 'href', 'routerLink', 'to', 'target' ],
    mounted() {
        
    },
    computed: {
        isDisabled() {
            return this.disabled && ( this.disabled.length === 0 || this.disabled === 'disabled' || this.disabled );
        },
    }
}
</script>