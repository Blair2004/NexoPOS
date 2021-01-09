<template>
    <div class="shadow-lg w-95vw md:w-3/5-screen bg-white">
        <div class="border-b border-gray-200 p-2 text-gray-700">
            Close Register
        </div>
        <div class="p-2">
            <div class="p-2 mb-2 bg-green-400 text-white font-bold text-right">
                {{ amount | currency }}
            </div>
            <ns-numpad :value="amount" @changed="setValue( $event )"></ns-numpad>
        </div>
    </div>
</template>
<script>
import nsNumpadVue from '@/components/ns-numpad.vue';
export default {
    components: {
        nsNumpad: nsNumpadVue
    },
    data() {
        return {
            amount: 0
        }
    },
    methods: {
        setValue( amount ) {
            this.amount     =   amount;
        }
    }
}
</script>