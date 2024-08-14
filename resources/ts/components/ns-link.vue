<template>
    <div class="flex">
        <template v-if="href">
            <a  :target="target" :href="href" :class="buttonclass" class="rounded cursor-pointer py-2 px-3 font-semibold"><slot></slot></a>
        </template>
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
    props: [ 'type', 'to', 'href', 'target' ],
    computed: {
        buttonclass() {
            switch( this.type ) {
                case 'info':
                    return 'shadow bg-info-secondary text-white'
                break;
                case 'success':
                    return 'shadow bg-success-secondary text-white'
                break;
                case 'error':
                    return 'shadow bg-error-secondary text-white'
                break;
                case 'warning':
                    return 'shadow bg-warning-secondary text-white'
                break;
                default:
                    return 'shadow bg-white text-gray-800'
                break;
            }
        }
    }
}
</script>